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

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\Asset\CustomFieldType\DropdownType;
use Glpi\Asset\CustomFieldType\StringType;
use Glpi\Asset\CustomFieldType\TextType;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Features\AssetImage;
use Glpi\Search\SearchOption;
use Group;
use Location;
use Manufacturer;
use Profile;
use Session;
use User;

/**
 * @extends AbstractDefinition<\Glpi\Asset\Asset>
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
            static fn (CapacityInterface $a, CapacityInterface $b) => strnatcasecmp($a->getLabel(), $b->getLabel())
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
                ]
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
        $field_match = array_filter($field_display, static fn ($field) => $field['key'] === $field_key);
        $field_options = [];
        if (!empty($field_match)) {
            $field_options = reset($field_match)['field_options'] ?? [];
        }
        // Merge field options with overrides
        $field_options = array_merge($field_options, $field_option_values);

        // Fake custom field to represent the core field
        $custom_field = new CustomFieldDefinition();
        $custom_field->fields['name'] = $field_key;
        $custom_field->fields['label'] = $all_fields[$field_key]['text'];
        $custom_field->fields['type'] = $all_fields[$field_key]['type'];
        $custom_field->fields['itemtype'] = \Computer::class; // Doesn't matter what it is as long as it's not empty
        $custom_field->fields['field_options'] = $field_options;

        $options_allowlist = ['required', 'readonly', 'full_width'];

        $twig_params = [
            'options' => array_filter($custom_field->getFieldType()->getOptions(), static fn ($option) => in_array($option->getKey(), $options_allowlist, true)),
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
            if (!$this->validateCapacityArray($input['capacities'])) {
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
                $input['capacities'] = json_encode($input['capacities']);
            }
        }

        if (array_key_exists('fields_display', $input)) {
            $formatted_fields_display = [];
            foreach ($input['fields_display'] as $field_order => $field_key) {
                $field_options = $input['field_options'][$field_key] ?? [];
                $formatted_fields_display[] = [
                    'order' => $field_order,
                    'key'   => $field_key,
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
        $added_capacities = @json_decode($this->fields['capacities']);
        foreach ($added_capacities as $capacity_classname) {
            $this->onCapacityEnabled($capacity_classname);
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
        $pref = new \DisplayPreference();
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
            $new_capacities = @json_decode($this->fields['capacities']);
            $old_capacities = @json_decode($this->oldvalues['capacities']);

            if (!is_array($new_capacities)) {
                // should not happen, do not trigger cleaning to prevent unexpected mass deletion of data
                trigger_error(
                    sprintf('Invalid `capacities` value `%s`.', $this->fields['capacities']),
                    E_USER_WARNING
                );
                return;
            }
            if (!is_array($old_capacities)) {
                // should not happen, do not trigger cleaning to prevent unexpected mass deletion of data
                trigger_error(
                    sprintf('Invalid `capacities` value `%s`.', $this->oldvalues['capacities']),
                    E_USER_WARNING
                );
                return;
            }

            $added_capacities = array_diff($new_capacities, $old_capacities);
            foreach ($added_capacities as $capacity_classname) {
                $this->onCapacityEnabled($capacity_classname);
            }

            $removed_capacities = array_diff($old_capacities, $new_capacities);
            foreach ($removed_capacities as $capacity_classname) {
                $this->onCapacityDisabled($capacity_classname);
            }
        }
    }

    public function cleanDBonPurge()
    {
        $capacities = $this->getDecodedCapacitiesField();
        foreach ($capacities as $capacity_classname) {
            $this->onCapacityDisabled($capacity_classname);
        }

        $related_classes = [
            $this->getAssetClassName(),
            $this->getAssetModelClassName(),
            $this->getAssetTypeClassName(),
        ];
        foreach ($related_classes as $classname) {
            (new $classname())->deleteByCriteria(
                ['assets_assetdefinitions_id' => $this->getID()],
                force: true,
                history: false
            );
            (new \DisplayPreference())->deleteByCriteria(['itemtype' => $classname]);
        }
    }

    /**
     * Handle the activation of a capacity.
     *
     * @phpstan-param class-string<\Glpi\Asset\Capacity\CapacityInterface> $capacity_classname
     */
    private function onCapacityEnabled(string $capacity_classname): void
    {
        $capacity = AssetDefinitionManager::getInstance()->getCapacity($capacity_classname);
        if ($capacity === null) {
            // can be null if provided by a plugin that is no longer active
            return;
        }
        $capacity->onCapacityEnabled($this->getAssetClassName());
    }

    /**
     * Handle the deactivation of a capacity.
     *
     * @phpstan-param class-string<\Glpi\Asset\Capacity\CapacityInterface> $capacity_classname
     */
    private function onCapacityDisabled(string $capacity_classname): void
    {
        $capacity = AssetDefinitionManager::getInstance()->getCapacity($capacity_classname);
        if ($capacity === null) {
            // can be null if provided by a plugin that is no longer active
            return;
        }
        $capacity->onCapacityDisabled($this->getAssetClassName());

        $rights_to_remove = $capacity->getSpecificRights();
        if (count($rights_to_remove) > 0) {
            $this->cleanRights($rights_to_remove);
        }
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'   => 'capacities',
            'name' => __('Capacities')
        ];
        foreach (AssetDefinitionManager::getInstance()->getAvailableCapacities() as $capacity) {
            // capacity is stored in a JSON array, so entry is surrounded by double quotes
            $search_string = json_encode($capacity::class);
            // Backslashes must be doubled in LIKE clause, according to MySQL documentation:
            // > To search for \, specify it as \\\\; this is because the backslashes are stripped
            // > once by the parser and again when the pattern match is made,
            // > leaving a single backslash to be matched against.
            $search_string = str_replace('\\', '\\\\', $search_string);

            $search_options[] = [
                'id'            => SearchOption::generateAProbablyUniqueId($capacity::class),
                'table'         => self::getTable(),
                'field'         => sprintf('_capacities_%s', $capacity::class),
                'name'          => $capacity->getLabel(),
                'computation'   => QueryFunction::if(
                    condition: ['capacities' => ['LIKE', '%' . $search_string . '%']],
                    true_expression: new QueryExpression('1'),
                    false_expression: new QueryExpression('0')
                ),
                'datatype'      => 'bool'
            ];
        }

        return $search_options;
    }

    /**
     * Get the definition's concrete asset class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<\Glpi\Asset\Asset>
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
     * @phpstan-return class-string<\Glpi\Asset\AssetModel>
     */
    public function getAssetModelClassName(bool $with_namespace = true): string
    {
        return $this->getAssetClassName($with_namespace) . 'Model';
    }

    /**
     * Get the definition's concrete asset type class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<\Glpi\Asset\AssetType>
     */
    public function getAssetTypeClassName(bool $with_namespace = true): string
    {
        return $this->getAssetClassName($with_namespace) . 'Type';
    }

    /**
     * Get the definition's concrete asset model dictionary class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<\Glpi\Asset\RuleDictionaryModel>
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
     * @phpstan-return class-string<\Glpi\Asset\RuleDictionaryModelCollection>
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
     * @phpstan-return class-string<\Glpi\Asset\RuleDictionaryType>
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
     * @phpstan-return class-string<\Glpi\Asset\RuleDictionaryTypeCollection>
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
        return in_array($capacity::class, $enabled_capacities);
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
                $capacities[] = $capacity;
            }
        }
        return $capacities;
    }

    /**
     * Return the decoded value of the `capacities` field.
     *
     * @return array
     */
    private function getDecodedCapacitiesField(): array
    {
        $capacities = @json_decode($this->fields['capacities'], associative: true);
        if (!$this->validateCapacityArray($capacities, false)) {
            trigger_error(
                sprintf('Invalid `capacities` value (`%s`).', $this->fields['capacities']),
                E_USER_WARNING
            );
            $this->fields['capacities'] = '[]'; // prevent warning to be triggered on each method call
            $capacities = [];
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
                'type' => StringType::class
            ],
            'states_id'        => [
                'text' => __('Status'),
                'type' => DropdownType::class
            ],
            'locations_id'     => [
                'text' => Location::getTypeName(1),
                'type' => DropdownType::class
            ],
            $type_class::getForeignKeyField() => [
                'text' => $type_class::getTypeName(1),
                'type' => DropdownType::class
            ],
            'users_id_tech'    => [
                'text' => __('Technician in charge'),
                'type' => DropdownType::class
            ],
            'manufacturers_id' => [
                'text' => Manufacturer::getTypeName(1),
                'type' => DropdownType::class
            ],
            'groups_id_tech'   => [
                'text' => __('Group in charge'),
                'type' => DropdownType::class
            ],
            $model_class::getForeignKeyField() => [
                'text' => $model_class::getTypeName(1),
                'type' => DropdownType::class
            ],
            'contact_num'      => [
                'text' => __('Alternate username number'),
                'type' => StringType::class
            ],
            'serial'           => [
                'text' => __('Serial'),
                'type' => StringType::class
            ],
            'contact'          => [
                'text' => __('Alternate username'),
                'type' => StringType::class
            ],
            'otherserial'      => [
                'text' => __('Inventory number'),
                'type' => StringType::class
            ],
            'users_id'         => [
                'text' => User::getTypeName(1),
                'type' => DropdownType::class
            ],
            'groups_id'        => [
                'text' => Group::getTypeName(1),
                'type' => DropdownType::class
            ],
            'uuid'            => [
                'text' => __('UUID'),
                'type' => StringType::class
            ],
            'comment'          => [
                'text' => _n('Comment', 'Comments', Session::getPluralNumber()),
                'type' => TextType::class
            ],
            'autoupdatesystems_id' => [
                'text' => \AutoUpdateSystem::getTypeName(1),
                'type' => DropdownType::class
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
        foreach ($all_fields as $key => $label) {
            $default[] = [
                'key'   => $key,
                'order' => $order,
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
            static fn ($a, $b) => $a['order'] <=> $b['order']
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
            fn ($capacity) => $capacity::class,
            AssetDefinitionManager::getInstance()->getAvailableCapacities()
        );
        foreach ($capacities as $classname) {
            if (!is_string($classname)) {
                $is_valid = false;
                break;
            }
            if ($check_values && !in_array($classname, $available_capacities)) {
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
        /** @var \DBmysql $DB */
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
            $helpdesk_item_types = json_decode($data['helpdesk_item_type'], associative: true) ?? [];
            if (!is_array($helpdesk_item_types)) {
                $helpdesk_item_types = [];
            }
            if (in_array($this->getCustomObjectClassName(), $helpdesk_item_types, true)) {
                $enabled_profiles[] = $data['id'];
            }
        }

        $twig_params = [
            'enabled_profiles' => $enabled_profiles,
            'label' => sprintf(__('Profiles that can associate %s with tickets, problems or changes'), $this->getTranslatedName(\Session::getPluralNumber())),
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
        /** @var \DBmysql $DB */
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
            $old_values[$data['id']] = json_decode($data['helpdesk_item_type'], associative: true);
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
            } else if (!$current_allowed && in_array($this->getCustomObjectClassName(), $itemtype_allowed, true)) {
                $changes['helpdesk_item_type'] = array_diff($itemtype_allowed, [$this->getCustomObjectClassName()]);
            }
            if (count($changes) > 0) {
                $profile = new Profile();
                $profile->update(['id' => $profile_id] + $changes);
            }
        }
    }
}
