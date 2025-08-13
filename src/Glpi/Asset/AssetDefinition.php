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

use AutoUpdateSystem;
use CommonGLPI;
use Computer;
use DisplayPreference;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\Features\AssetImage;
use Group;
use Location;
use LogicException;
use Manufacturer;
use Profile;
use Session;
use User;

use function Safe\json_decode;
use function Safe\json_encode;

/**
 * @extends AbstractDefinition<Asset>
 */
final class AssetDefinition extends AbstractDefinition
{
    use AssetImage;

    public static function getSectorizedDetails(): array
    {
        return ['config', self::class];
    }

    public static function getCustomObjectBaseClass(): string
    {
        return Asset::class;
    }

    public static function getCustomObjectNamespace(): string
    {
        return 'Glpi\\CustomAsset';
    }

    public static function getCustomObjectClassSuffix(): string
    {
        return 'Asset';
    }

    public static function getDefinitionManagerClass(): string
    {
        return AssetDefinitionManager::class;
    }

    public function getCustomObjectRightname(): string
    {
        return sprintf('asset_%s', strtolower($this->fields['system_name']));
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Asset definition', 'Asset definitions', $nb);
    }

    protected function computeFriendlyName()
    {
        return $this->getTranslatedName();
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof self) {
            $capacities_count   = 0;
            $profiles_count     = 0;
            $translations_count = 0;
            $fields_count = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $capacities_count   = count($item->getDecodedCapacitiesField());
                $profiles_count     = count(array_filter($item->getDecodedProfilesField()));
                $translations_count = count($item->getDecodedTranslationsField());
                $fields_count = count($item->getDecodedFieldsField());
            }
            return [
                1 => self::createTabEntry(
                    __('Capacities'),
                    $capacities_count,
                    self::class,
                    'ti ti-adjustments'
                ),
                2 => self::createTabEntry(
                    __('Fields'),
                    $fields_count,
                    self::class,
                    'ti ti-forms'
                ),
                3 => self::createTabEntry(
                    _n('Profile', 'Profiles', Session::getPluralNumber()),
                    $profiles_count,
                    self::class,
                    'ti ti-user-check'
                ),
                4 => self::createTabEntry(
                    _n('Translation', 'Translations', Session::getPluralNumber()),
                    $translations_count,
                    self::class,
                    'ti ti-language'
                ),
            ];
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof self) {
            switch ($tabnum) {
                case 1:
                    $item->showCapacitiesForm();
                    break;
                case 2:
                    $item->showFieldsForm();
                    break;
                case 3:
                    $item->showProfilesForm();
                    break;
                case 4:
                    $item->showTranslationForm();
                    break;
            }
        }
        return true;
    }

    /**
     * Display capacities form.
     *
     * @return void
     */
    private function showCapacitiesForm(): void
    {
        $capacities = AssetDefinitionManager::getInstance()->getAvailableCapacities();
        usort(
            $capacities,
            static fn(CapacityInterface $a, CapacityInterface $b) => strnatcasecmp($a->getLabel(), $b->getLabel())
        );

        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/capacities.html.twig',
            [
                'item' => $this,
                'classname' => $this->getAssetClassName(),
                'capacities' => $capacities,
            ]
        );
    }

    /*
     * Display fields form.
     *
     * @return void
     */
    private function showFieldsForm(): void
    {
        $fields_display = $this->getDecodedFieldsField();

        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/fields_display.html.twig',
            [
                'item'           => $this,
                'all_fields'     => $this->getAllFields(),
                'fields_display' => $fields_display,
                'can_create_fields' => CustomFieldDefinition::canCreate(),
                'custom_field_form_params' => [
                    'id' => $this->fields['id'],
                    'type' => CustomFieldDefinition::class,
                    'parenttype' => CustomFieldDefinition::$itemtype,
                    'items_id' => CustomFieldDefinition::$items_id,
                    'subitem_container_id' => 'customfield_form_container',
                    'as_modal' => true,
                    'ajax_form_submit' => true,
                ],
            ]
        );
    }

    /**
     * Show field options for a core field.
     * @param string $field_key The field key
     * @param array $field_option_values Field option value overrides
     * @return void
     */
    public function showFieldOptionsForCoreField(string $field_key, array $field_option_values = []): void
    {
        $all_fields = $this->getAllFields();
        $field_display = $this->getDecodedFieldsField();
        $field_match = array_filter($field_display, static fn($field) => $field['key'] === $field_key);
        $field_options = [];
        if ($field_match !== []) {
            $field_options = reset($field_match)['field_options'] ?? [];
        }
        // Merge field options with overrides
        $field_options = array_merge($field_options, $field_option_values);

        // Fake custom field to represent the core field
        $custom_field = new CustomFieldDefinition();
        $custom_field->fields['name'] = $field_key;
        $custom_field->fields['label'] = $all_fields[$field_key]['text'];
        $custom_field->fields['type'] = $all_fields[$field_key]['type'];
        $custom_field->fields['itemtype'] = Computer::class; // Doesn't matter what it is as long as it's not empty
        $custom_field->fields['field_options'] = $field_options;

        $options_allowlist = ['required', 'readonly', 'full_width', 'hidden'];

        $twig_params = [
            'options' => array_filter($custom_field->getFieldType()->getOptions(), static fn($option) => in_array($option->getKey(), $options_allowlist, true)),
            'key' => $field_key,
        ];

        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <form>
                <input type="hidden" name="key" value="{{ key }}">
                <div class="d-flex flex-wrap">
                    {% for option in options %}
                        {{ option.getFormInput()|raw }}
                    {% endfor %}
                </div>
            </form>
TWIG, $twig_params);
    }

    public function prepareInputForAdd($input)
    {
        foreach (['capacities', 'profiles', 'translations', 'fields_display'] as $json_field) {
            if (!array_key_exists($json_field, $input)) {
                // ensure default value of JSON fields will be a valid array
                $input[$json_field] = [];
            }
        }
        $input = $this->managePictures($input);

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->managePictures($input);
        return parent::prepareInputForUpdate($input);
    }

    protected function prepareInput(array $input): array|bool
    {
        $has_errors = false;

        if (array_key_exists('capacities', $input)) {
            $capacities = $input['capacities'];

            // Filter capacities submitted by the UI.
            // A `is_active` field will be present in this case, to be able to remove inactive capacities.
            if (\is_array($capacities)) {
                foreach ($capacities as $key => $capacity_specs) {
                    if (
                        \is_array($capacity_specs)
                        && \array_key_exists('is_active', $capacity_specs)
                        && ((bool) $capacity_specs['is_active']) === false
                    ) {
                        unset($capacities[$key]);
                    }
                }
            }

            if (!$this->validateCapacityArray($capacities)) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        __('Capacities')
                    )),
                    false,
                    ERROR
                );
                $has_errors = true;
            } else {
                // Add the config key if not present in the input.
                $capacities = \array_map(
                    fn(array $capacity_specs) => new Capacity(
                        $capacity_specs['name'],
                        new CapacityConfig($capacity_specs['config'] ?? [])
                    ),
                    $capacities
                );

                $input['capacities'] = json_encode(array_values($capacities));
            }
        }

        if (array_key_exists('fields_display', $input)) {
            $formatted_fields_display = [];
            foreach ($input['fields_display'] as $field_order => $field_key) {
                $field_options = $input['field_options'][$field_key] ?? [];
                $formatted_fields_display[] = [
                    'key'   => $field_key,
                    'order' => $field_order,
                    'field_options' => $field_options,
                ];
            }
            $input['fields_display'] = json_encode($formatted_fields_display);
        }

        return $has_errors ? false : parent::prepareInput($input);
    }

    public function post_addItem()
    {
        parent::post_addItem();

        // Trigger the `onCapacityEnabled` hooks.
        $added_capacities = $this->decodeCapacities($this->fields['capacities']);
        foreach ($added_capacities as $capacity) {
            $this->onCapacityEnabled($capacity);
        }

        // Add default display preferences for the new asset definition
        $prefs = [
            4, // Name
            40, // Model
            5, // Serial
            23, // Manufacturer
            31, // Status
            3, // Location
            19, // Last Update
        ];
        $pref = new DisplayPreference();
        foreach ($prefs as $field) {
            $pref->add([
                'itemtype' => $this->getAssetClassName(),
                'num'      => $field,
                'users_id' => 0,
            ]);
        }
    }

    public function post_updateItem($history = true)
    {
        parent::post_updateItem();

        if (in_array('capacities', $this->updates)) {
            $new_capacities = $this->decodeCapacities($this->fields['capacities']);
            $old_capacities = $this->decodeCapacities($this->oldvalues['capacities']);

            $added_capacities = array_diff_key($new_capacities, $old_capacities);
            foreach ($added_capacities as $capacity) {
                $this->onCapacityEnabled($capacity);
            }

            $removed_capacities = array_diff_key($old_capacities, $new_capacities);
            foreach ($removed_capacities as $capacity) {
                $this->onCapacityDisabled($capacity);
            }

            $updated_capacities = array_intersect_key($old_capacities, $new_capacities);
            foreach ($updated_capacities as $capacity) {
                $this->onCapacityUpdated($capacity);
            }
        }
    }

    public function cleanDBonPurge()
    {
        $capacities = $this->getDecodedCapacitiesField();
        foreach ($capacities as $capacity) {
            $this->onCapacityDisabled($capacity);
        }

        $this->purgeConcreteClassFromDb($this->getCustomObjectClassName());
        $this->purgeConcreteClassFromDb($this->getAssetModelClassName());
        $this->purgeConcreteClassFromDb($this->getAssetTypeClassName());
    }

    /**
     * Handle the activation of a capacity.
     *
     * @param Capacity $capacity
     */
    private function onCapacityEnabled(Capacity $capacity): void
    {
        $capacity_instance = AssetDefinitionManager::getInstance()->getCapacity($capacity->getName());
        if ($capacity_instance === null) {
            // can be null if provided by a plugin that is no longer active
            return;
        }
        $capacity_instance->onCapacityEnabled($this->getAssetClassName(), $capacity->getConfig());
    }

    /**
     * Handle the deactivation of a capacity.
     *
     * @param Capacity $capacity
     */
    private function onCapacityDisabled(Capacity $capacity): void
    {
        $capacity_instance = AssetDefinitionManager::getInstance()->getCapacity($capacity->getName());
        if ($capacity_instance === null) {
            // can be null if provided by a plugin that is no longer active
            return;
        }
        $capacity_instance->onCapacityDisabled($this->getAssetClassName(), $capacity->getConfig());

        $rights_to_remove = $capacity_instance->getSpecificRights();
        if (count($rights_to_remove) > 0) {
            $this->cleanRights($rights_to_remove);
        }
    }

    /**
     * Handle the update of a capacity.
     *
     * @param Capacity $capacity
     */
    private function onCapacityUpdated(Capacity $capacity): void
    {
        $capacity_instance = AssetDefinitionManager::getInstance()->getCapacity($capacity->getName());
        if ($capacity_instance === null) {
            // can be null if provided by a plugin that is no longer active
            return;
        }
        $updated_capacity = $this->getDecodedCapacitiesField()[$capacity->getName()];
        $capacity_instance->onCapacityUpdated($this->getAssetClassName(), $capacity->getConfig(), $updated_capacity->getConfig());
    }

    /**
     * Get the definition's concrete asset class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<Asset>
     */
    public function getAssetClassName(bool $with_namespace = true): string
    {
        return $this->getCustomObjectClassName($with_namespace);
    }

    /**
     * Get the definition's concrete asset model class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<AssetModel>
     */
    public function getAssetModelClassName(bool $with_namespace = true): string
    {
        return $this->getAssetClassName($with_namespace) . 'Model';
    }

    public function getAssetModelClassInstance(): AssetModel
    {
        $classname = $this->getAssetModelClassName();

        if (!\is_a($classname, AssetModel::class, true)) {
            throw new LogicException();
        }

        return new $classname();
    }

    /**
     * Get the definition's concrete asset type class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<AssetType>
     */
    public function getAssetTypeClassName(bool $with_namespace = true): string
    {
        return $this->getAssetClassName($with_namespace) . 'Type';
    }

    public function getAssetTypeClassInstance(): AssetType
    {
        $classname = $this->getAssetTypeClassName();

        if (!\is_a($classname, AssetType::class, true)) {
            throw new LogicException();
        }

        return new $classname();
    }

    /**
     * Get the definition's concrete asset model dictionary class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<RuleDictionaryModel>
     */
    public function getAssetModelDictionaryClassName(bool $with_namespace = true): string
    {
        $classname = 'RuleDictionary' . $this->getAssetModelClassName(false);
        if ($with_namespace) {
            $classname = static::getCustomObjectNamespace() . '\\' . $classname;
        }
        return $classname;
    }

    /**
     * Get the definition's concrete asset model dictionary collection class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<RuleDictionaryModelCollection>
     */
    public function getAssetModelDictionaryCollectionClassName(bool $with_namespace = true): string
    {
        $classname = $this->getAssetModelDictionaryClassName(false) . 'Collection';
        if ($with_namespace) {
            $classname = static::getCustomObjectNamespace() . '\\' . $classname;
        }
        return $classname;
    }

    /**
     * Get the definition's concrete asset type dictionary class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<RuleDictionaryType>
     */
    public function getAssetTypeDictionaryClassName(bool $with_namespace = true): string
    {
        $classname = 'RuleDictionary' . $this->getAssetTypeClassName(false);
        if ($with_namespace) {
            $classname = static::getCustomObjectNamespace() . '\\' . $classname;
        }
        return $classname;
    }

    /**
     * Get the definition's concrete asset type dictionary collection class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<RuleDictionaryTypeCollection>
     */
    public function getAssetTypeDictionaryCollectionClassName(bool $with_namespace = true): string
    {
        $classname = $this->getAssetTypeDictionaryClassName(false) . 'Collection';
        if ($with_namespace) {
            $classname = static::getCustomObjectNamespace() . '\\' . $classname;
        }
        return $classname;
    }

    /**
     * Indicates whether the given capacity is enabled.
     *
     * @param CapacityInterface $capacity
     * @return bool
     */
    public function hasCapacityEnabled(CapacityInterface $capacity): bool
    {
        $enabled_capacities = $this->getDecodedCapacitiesField();
        return isset($enabled_capacities[$capacity::class]);
    }

    /**
     * Get the list of enabled capacities.
     *
     * @return CapacityInterface[]
     */
    public function getEnabledCapacities(): array
    {
        $capacities = [];
        foreach (AssetDefinitionManager::getInstance()->getAvailableCapacities() as $capacity) {
            if ($this->hasCapacityEnabled($capacity)) {
                $capacities[$capacity::class] = $capacity;
            }
        }
        return $capacities;
    }

    /**
     * Get configuration for the given capacity.
     */
    public function getCapacityConfiguration(string $capacity_classname): CapacityConfig
    {
        $capacities = $this->getDecodedCapacitiesField();
        if (isset($capacities[$capacity_classname])) {
            return $capacities[$capacity_classname]->getConfig();
        }

        return new CapacityConfig();
    }

    /**
     * Return the decoded value of the `capacities` field.
     *
     * @return Capacity[]
     */
    private function getDecodedCapacitiesField(): array
    {
        return $this->decodeCapacities($this->fields['capacities']);
    }

    /**
     * Decoded the given value of the `capacities` field.
     *
     * @return Capacity[]
     */
    private function decodeCapacities(string $encoded): array
    {
        $decoded_capacities = json_decode($encoded, associative: true);

        if (!$this->validateCapacityArray($decoded_capacities, false)) {
            trigger_error(
                sprintf('Invalid `capacities` value (`%s`).', $this->fields['capacities']),
                E_USER_WARNING
            );
            $this->fields['capacities'] = '[]'; // prevent warning to be triggered on each method call
            return [];
        }

        $capacities = [];
        foreach ($decoded_capacities as $capacity_specs) {
            $name   = $capacity_specs['name'];
            $config = $capacity_specs['config'] ?? [];

            if (!\is_a($name, CapacityInterface::class, true)) {
                // May be a previously enabled capacity from a disabled plugin.
                continue;
            }

            $capacities[$name] = new Capacity($name, new CapacityConfig($config));
        }

        return $capacities;
    }


    public function getAllFields(): array
    {
        $type_class = $this->getAssetTypeClassName();
        $model_class = $this->getAssetModelClassName();

        $fields = [
            'name'             => [
                'text' => __('Name'),
                'type' => StringType::class,
            ],
            'states_id'        => [
                'text' => __('Status'),
                'type' => DropdownType::class,
            ],
            'locations_id'     => [
                'text' => Location::getTypeName(1),
                'type' => DropdownType::class,
            ],
            $type_class::getForeignKeyField() => [
                'text' => $type_class::getTypeName(1),
                'type' => DropdownType::class,
            ],
            'users_id_tech'    => [
                'text' => __('Technician in charge'),
                'type' => DropdownType::class,
            ],
            'manufacturers_id' => [
                'text' => Manufacturer::getTypeName(1),
                'type' => DropdownType::class,
            ],
            'groups_id_tech'   => [
                'text' => __('Group in charge'),
                'type' => DropdownType::class,
            ],
            $model_class::getForeignKeyField() => [
                'text' => $model_class::getTypeName(1),
                'type' => DropdownType::class,
            ],
            'contact_num'      => [
                'text' => __('Alternate username number'),
                'type' => StringType::class,
            ],
            'serial'           => [
                'text' => __('Serial'),
                'type' => StringType::class,
            ],
            'contact'          => [
                'text' => __('Alternate username'),
                'type' => StringType::class,
            ],
            'otherserial'      => [
                'text' => __('Inventory number'),
                'type' => StringType::class,
            ],
            'users_id'         => [
                'text' => User::getTypeName(1),
                'type' => DropdownType::class,
            ],
            'groups_id'        => [
                'text' => Group::getTypeName(1),
                'type' => DropdownType::class,
            ],
            'uuid'            => [
                'text' => __('UUID'),
                'type' => StringType::class,
            ],
            'comment'          => [
                'text' => _n('Comment', 'Comments', Session::getPluralNumber()),
                'type' => TextType::class,
            ],
            'autoupdatesystems_id' => [
                'text' => AutoUpdateSystem::getTypeName(1),
                'type' => DropdownType::class,
            ],
        ];

        foreach ($this->getCustomFieldDefinitions() as $custom_field_def) {
            $fields['custom_' . $custom_field_def->fields['system_name']] = [
                'customfields_id'    => $custom_field_def->getID(),
                'text' => $custom_field_def->computeFriendlyName(),
                'type' => $custom_field_def->fields['type'],
            ];
        }

        return $fields;
    }

    private function getDefaultFieldsDisplay(): array
    {
        $all_fields = $this->getAllFields();

        $default = [];
        $order = 0;
        foreach (array_keys($all_fields) as $key) {
            $default[] = [
                'key'   => $key,
                'order' => $order,
                'field_options' => [],
            ];
            $order++;
        }

        return $default;
    }


    /**
     * Return the decoded value of the `fields_display` field.
     *
     * @return array
     */
    public function getDecodedFieldsField(): array
    {
        $fields_display = json_decode($this->fields['fields_display'] ?? '[]', associative: true) ?? [];
        if (!is_array($fields_display) || count($fields_display) === 0) {
            return $this->getDefaultFieldsDisplay();
        }
        return $fields_display;
    }

    public function getFieldOrder(): array
    {
        $fields_display = $this->getDecodedFieldsField();
        usort(
            $fields_display,
            static fn($a, $b) => $a['order'] <=> $b['order']
        );
        return array_column($fields_display, 'key');
    }

    /**
     * Validate that the given capacities array contains valid values.
     *
     * @param mixed $capacities
     * @param bool $check_values
     * @return bool
     */
    private function validateCapacityArray(mixed $capacities, bool $check_values = true): bool
    {
        if (!is_array($capacities)) {
            return false;
        }

        $is_valid = true;

        $available_capacities = array_map(
            fn($capacity) => $capacity::class,
            AssetDefinitionManager::getInstance()->getAvailableCapacities()
        );
        foreach ($capacities as $capacity_specs) {
            if (!is_array($capacity_specs)) {
                $is_valid = false;
                break;
            }

            if (!\array_key_exists('name', $capacity_specs) || !\is_string($capacity_specs['name'])) {
                $is_valid = false;
                break;
            }
            if (\array_key_exists('config', $capacity_specs) && !\is_array($capacity_specs['config'])) {
                // FIXME A configuration check delegated to the capacity class would be safer.
                $is_valid = false;
                break;
            }

            if ($check_values && !in_array($capacity_specs['name'], $available_capacities)) {
                $is_valid = false;
                break;
            }
        }

        return $is_valid;
    }

    /**
     * @return CustomFieldDefinition[]
     */
    public function getCustomFieldDefinitions(): array
    {
        global $DB;

        if ($this->custom_field_definitions === null) {
            $this->custom_field_definitions = [];
            $it = $DB->request([
                'FROM'   => CustomFieldDefinition::getTable(),
                'WHERE'  => [
                    self::getForeignKeyField() => $this->getID(),
                ],
            ]);

            $available_types = AssetDefinitionManager::getInstance()->getCustomFieldTypes();
            foreach ($it as $field) {
                if (!in_array($field['type'], $available_types, true)) {
                    continue;
                }
                $custom_field = new CustomFieldDefinition();
                $custom_field->getFromResultSet($field);
                $custom_field->post_getFromDB();
                $this->custom_field_definitions[] = $custom_field;
            }
        }

        return $this->custom_field_definitions;
    }

    protected function getExtraProfilesFields(array $profile_data): string
    {
        $enabled_profiles = [];
        foreach ($profile_data as $data) {
            $helpdesk_item_types = importArrayFromDB($data['helpdesk_item_type']);
            if (in_array($this->getCustomObjectClassName(), $helpdesk_item_types, true)) {
                $enabled_profiles[] = $data['id'];
            }
        }

        $twig_params = [
            'enabled_profiles' => $enabled_profiles,
            'label' => sprintf(__('Profiles that can associate %s with tickets, problems or changes'), $this->getTranslatedName(Session::getPluralNumber())),
        ];
        // language=Twig
        return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            {% import 'components/form/fields_macros.html.twig' as fields %}
            {{ fields.dropdownField('Profile', '_profiles_extra[helpdesk_item_type]', enabled_profiles, label, {
                multiple: true
            }) }}
TWIG, $twig_params);
    }

    protected function syncProfilesRights(): void
    {
        global $DB;

        parent::syncProfilesRights();

        if (
            !array_key_exists('_profiles_extra', $this->input)
            || !array_key_exists('helpdesk_item_type', $this->input['_profiles_extra'])
        ) {
            return;
        }

        $extra_profile_data = $this->input['_profiles_extra'];
        $old_values = [];

        $it = $DB->request([
            'SELECT' => ['id', 'helpdesk_item_type'],
            'FROM' => Profile::getTable(),
        ]);
        foreach ($it as $data) {
            $old_values[$data['id']] = importArrayFromDB($data['helpdesk_item_type']);
            if (!is_array($old_values[$data['id']])) {
                $old_values[$data['id']] = [];
            }
        }

        $helpdesk_item_type = $extra_profile_data['helpdesk_item_type'];
        if (!is_array($helpdesk_item_type)) {
            // `helpdesk_item_type` will be an empty string if no value is selected
            $helpdesk_item_type = [];
        }

        foreach ($old_values as $profile_id => $itemtype_allowed) {
            $changes = [];

            $current_allowed = in_array($profile_id, $helpdesk_item_type, false);
            if ($current_allowed && !in_array($this->getCustomObjectClassName(), $itemtype_allowed, true)) {
                $changes['helpdesk_item_type'] = [...$itemtype_allowed, $this->getCustomObjectClassName()];
            } elseif (!$current_allowed && in_array($this->getCustomObjectClassName(), $itemtype_allowed, true)) {
                $changes['helpdesk_item_type'] = array_diff($itemtype_allowed, [$this->getCustomObjectClassName()]);
            }
            if (count($changes) > 0) {
                $profile = new Profile();
                $profile->update(['id' => $profile_id] + $changes);
            }
        }
    }

    /**
     * Return the SQL system criteria to be used by the asset concrete classes.
     */
    public function getSystemSQLCriteriaForConcreteClass(?string $tablename = null): array
    {
        $table_prefix = $tablename !== null
            ? $tablename . '.'
            : '';

        // Keep only items from current definition must be shown.
        $criteria = [
            $table_prefix . self::getForeignKeyField() => $this->getID(),
        ];

        // Add another layer to the array to prevent losing duplicates keys if the
        // result of the function is merged with another array.
        $criteria = [crc32(serialize($criteria)) => $criteria];

        return $criteria;
    }
}
