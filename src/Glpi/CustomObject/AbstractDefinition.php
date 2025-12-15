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

namespace Glpi\CustomObject;

use CommonDBTM;
use DisplayPreference;
use Dropdown;
use Gettext\Languages\Category as Language_Category;
use Gettext\Languages\CldrData as Language_CldrData;
use Gettext\Languages\Language;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\CustomFieldDefinition;
use LogicException;
use Profile;
use ProfileRight;
use Session;

use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\preg_match;

/**
 * Abstract class for custom object definition managers
 * @template ConcreteClass of CommonDBTM
 */
abstract class AbstractDefinition extends CommonDBTM
{
    /**
     * System name regex pattern.
     *
     * 1. Must start with a letter.
     * 2. Must contain only letters, numbers, or underscores.
     * 3. Must not end with an underscore.
     */
    public const SYSTEM_NAME_PATTERN = '[A-Za-z][A-Za-z0-9_]*[A-Za-z0-9]';

    public static $rightname = 'config';

    /**
     * @var CustomFieldDefinition[]|null
     * @see self::getCustomFieldDefinitions()
     */
    protected ?array $custom_field_definitions = null;

    /**
     * Get the base class for custom objects of this type.
     * @return class-string<ConcreteClass>
     */
    abstract public static function getCustomObjectBaseClass(): string;

    /**
     * Get the namespace that custom object classes of this type will be created in.
     * @return string
     */
    abstract public static function getCustomObjectNamespace(): string;

    /**
     * Get the suffix to add to custom object classes.
     * @return string
     */
    abstract public static function getCustomObjectClassSuffix(): string;

    /**
     * Get the class name for the definition manager of this type.
     * @return class-string<AbstractDefinitionManager>
     */
    abstract public static function getDefinitionManagerClass(): string;

    /**
     * Get the CSS class name for the icon of the objects of this type.
     * @return string
     */
    public function getCustomObjectIcon(): string
    {
        return 'ti ' . ($this->fields['icon'] ?: 'ti-box');
    }

    /**
     * Get the name of the class for objects of this type
     *
     * @param bool $with_namespace Whether to include the namespace in the class name
     *
     * @return string
     * @phpstan-return class-string<ConcreteClass>
     */
    public function getCustomObjectClassName(bool $with_namespace = true): string
    {
        $classname = $this->fields['system_name'] . static::getCustomObjectClassSuffix();

        if ($with_namespace) {
            $classname = static::getCustomObjectNamespace() . '\\' . $classname;
        }

        return $classname;
    }

    /**
     * @phpstan-return ConcreteClass
     */
    public function getCustomObjectClassInstance(): CommonDBTM
    {
        $classname = $this->getCustomObjectClassName();

        if (!is_a($classname, CommonDBTM::class, true)) {
            throw new LogicException();
        }

        return new $classname();
    }

    /**
     * Get the rightname for the custom objects of this type.
     * @return string
     */
    abstract public function getCustomObjectRightname(): string;

    /**
     * Get the list of possible rights for the objects of this type
     * @return array<int, string|array>
     */
    protected function getPossibleCustomObjectRights(): array
    {
        return $this->getCustomObjectClassInstance()->getRights();
    }

    public static function getNameField()
    {
        return 'label';
    }

    public static function getIcon()
    {
        return 'ti ti-database-cog';
    }

    public static function canCreate(): bool
    {
        // required due to usage of `config` rightname
        return self::canUpdate();
    }

    public static function canPurge(): bool
    {
        // required due to usage of `config` rightname
        return self::canUpdate();
    }

    /**
     * Load instance related to given system name.
     *
     * @param string $system_name
     * @return bool
     */
    public function getFromDBBySystemName(string $system_name): bool
    {
        return $this->getFromDBByCrit(['system_name' => $system_name]);
    }

    public function defineTabs($options = [])
    {
        $tabs = [];

        $this->addDefaultFormTab($tabs);
        $this->addStandardTab(static::class, $tabs, $options);

        return $tabs;
    }

    public function showForm($ID, array $options = [])
    {
        global $DB;

        $this->initForm($ID, $options);
        $options['candel'] = false;

        $item_count = 0;
        $item_type = static::getCustomObjectBaseClass();

        if (!self::isNewID($ID)) {
            $count_conditions = [
                static::getForeignKeyField() => $ID,
            ];
            if ($DB->fieldExists($item_type::getTable(), 'is_template')) {
                $count_conditions['is_template'] = 0;
            }
            $item_count = countElementsInTable(
                table: static::getCustomObjectBaseClass()::getTable(),
                condition: $count_conditions
            );
            $options['addbuttons'] = [
                'purge' => [
                    'title' => _x('button', 'Delete permanently'),
                    'add_class' => 'btn-outline-danger',
                    'icon' => 'ti ti-trash',
                    'text' => _x('button', 'Delete permanently'),
                    'type' => 'submit',
                ],
            ];
        }

        $definition_manager = static::getDefinitionManagerClass()::getInstance();
        TemplateRenderer::getInstance()->display(
            'pages/admin/customobjects/main.html.twig',
            [
                'item'                  => $this,
                'params'                => $options,
                'has_rights_enabled'    => $this->hasRightsEnabled(),
                'reserved_system_names_pattern' => $definition_manager->getReservedSystemNamesPattern(),
                'existing_system_names' => array_values(array_filter(array_map(
                    static fn(self $definition) => $definition->fields['system_name'],
                    $definition_manager->getDefinitions()
                ), fn($name) => $name !== $this->fields['system_name'])),
                'item_count'            => $item_count,
            ]
        );
        return true;
    }

    /**
     * Display profiles form.
     *
     * @return void
     */
    protected function showProfilesForm(): void
    {
        global $DB;

        $possible_rights = $this->getPossibleCustomObjectRights();

        $profiles_data = iterator_to_array(
            $DB->request([
                'FROM'  => Profile::getTable(),
                'ORDER' => 'name ASC',
            ])
        );

        $central_profiles = \array_filter(
            $profiles_data,
            static fn(array $profile) => $profile['interface'] !== 'heldesk'
        );

        $nb_cb_per_col = array_fill_keys(
            array_keys($possible_rights),
            [
                'checked' => 0,
                'total' => count($central_profiles),
            ]
        );
        $nb_cb_per_row = [];

        $matrix_rows = [];
        foreach ($central_profiles as $profile_data) {
            $profile_id = $profile_data['id'];
            $profile_rights = $this->getRightsForProfile($profile_id);

            $checkbox_key = sprintf('profiles[%d]', $profile_id);

            $nb_cb_per_row[$checkbox_key] = [
                'checked' => 0,
                'total' => count($possible_rights),
            ];

            $row = [
                'label' => $profile_data['name'],
                'columns' => [],
            ];
            foreach (array_keys($possible_rights) as $right_value) {
                $checked = $profile_rights & $right_value;
                $row['columns'][$right_value] = [
                    'checked' => $checked,
                ];

                if ($checked) {
                    $nb_cb_per_row[$checkbox_key]['checked']++;
                    $nb_cb_per_col[$right_value]['checked']++;
                }
            }
            $matrix_rows[$checkbox_key] = $row;
        }

        TemplateRenderer::getInstance()->display(
            'pages/admin/customobjects/profiles.html.twig',
            [
                'item'           => $this,
                'matrix_columns' => $possible_rights,
                'matrix_rows'    => $matrix_rows,
                'nb_cb_per_col'  => $nb_cb_per_col,
                'nb_cb_per_row'  => $nb_cb_per_row,
                'extra_fields'   => $this->getExtraProfilesFields($profiles_data),
            ]
        );
    }

    /**
     * Display translation form.
     *
     * @return void
     */
    protected function showTranslationForm(): void
    {
        global $CFG_GLPI;

        $asset_name_translations = $this->getDecodedTranslationsField();

        $translations = [];
        foreach ($asset_name_translations as $lang => $plurals) {
            $translations[] = [
                'id' => 0,
                'name' => __('Asset name'),
                'language' => $lang,
                'translation' => $plurals,
            ];
        }

        $custom_fields = [];
        $custom_field_definitions = $this->getCustomFieldDefinitions();
        foreach ($custom_field_definitions as $custom_field) {
            $custom_fields[$custom_field->getID()] = $custom_field->fields['system_name'];
            foreach ($custom_field->getDecodedTranslationsField() as $language => $translation) {
                $translations[] = [
                    'id' => $custom_field->getID(),
                    'name' => $custom_field->fields['system_name'],
                    'language' => $language,
                    'translation' => ['one' => $translation],
                ];
            }
        }

        usort(
            $translations,
            static fn(array $a, array $b) => strnatcasecmp($CFG_GLPI['languages'][$a['language']][0], $CFG_GLPI['languages'][$b['language']][0])
        );

        $rand = mt_rand();

        TemplateRenderer::getInstance()->display(
            'pages/admin/customobjects/translations.html.twig',
            [
                'item' => $this,
                'classname' => $this->getCustomObjectClassName(),
                'translations' => $translations,
                'fields_dropdown' => Dropdown::showFromArray('field', [0 => __('Asset name')] + $custom_fields, [
                    'display'             => false,
                    'display_emptychoice' => false,
                    'width'               => '100%',
                    'rand'                => $rand,
                ]),
                'languages_dropdown' => Dropdown::showLanguages('language', [
                    'display'             => false,
                    'display_emptychoice' => true,
                    'width'               => '100%',
                    'rand'                => $rand,
                ]),
                'rand' => $rand,
            ]
        );
    }

    public function prepareInputForAdd($input)
    {
        if (!array_key_exists('system_name', $input)) {
            Session::addMessageAfterRedirect(
                __s('The system name is mandatory.'),
                false,
                ERROR
            );
            return false;
        }

        if (
            !is_string($input['system_name'])
            || preg_match('/^' . self::SYSTEM_NAME_PATTERN . '$/', $input['system_name']) !== 1
        ) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('The following field has an incorrect value: "%s".'),
                    __('System name')
                )),
                false,
                ERROR
            );
            return false;
        } elseif (preg_match(static::getDefinitionManagerClass()::getInstance()->getReservedSystemNamesPattern(), $input['system_name']) === 1) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(
                    __('The system name is a reserved name.'),
                    $input['system_name']
                )),
                false,
                ERROR
            );
            return false;
        } else {
            $existing_system_names = array_map(static fn($d) => strtolower($d->fields['system_name'] ?? ''), static::getDefinitionManagerClass()::getInstance()->getDefinitions());
            if (in_array(strtolower($input['system_name']), $existing_system_names, true)) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('The system name must be unique.'),
                        $input['system_name']
                    )),
                    false,
                    ERROR
                );
                return false;
            }
        }

        if (empty($input['label'])) {
            $input['label'] = $input['system_name'];
        }
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (
            array_key_exists('system_name', $input)
            && $input['system_name'] !== $this->fields['system_name']
        ) {
            Session::addMessageAfterRedirect(
                __s('The system name cannot be changed.'),
                false,
                ERROR
            );
            return false;
        }

        if (isset($input['_save_translation'], $input['language'], $input['plurals'])) {
            if (empty($input['field'] ?? null)) {
                $translations = $this->getDecodedTranslationsField();
                $translations[$input['language']] = $input['plurals'];
                unset($input['_save_translation'], $input['language'], $input['plurals']);
                $input['translations'] = $translations;
            } elseif (array_key_exists('one', $input['plurals'])) {
                $custom_field = new CustomFieldDefinition();
                if ($custom_field->getFromDB($input['field']) && $custom_field->fields[static::getForeignKeyField()] === $this->getID()) {
                    $translations = $custom_field->getDecodedTranslationsField();
                    $translations[$input['language']] = $input['plurals']['one'];
                    $custom_field->update([
                        'id' => $input['field'],
                        'translations' => $translations,
                    ]);
                }
            }
        }

        if (isset($input['_delete_translation'], $input['language'])) {
            if (empty($input['field'] ?? null)) {
                $translations = $this->getDecodedTranslationsField();
                unset($translations[$input['language']], $input['_delete_translation'], $input['language']);
                $input['translations'] = $translations;
            } else {
                $custom_field = new CustomFieldDefinition();
                if ($custom_field->getFromDB($input['field']) && $custom_field->fields[static::getForeignKeyField()] === $this->getID()) {
                    $translations = $custom_field->getDecodedTranslationsField();
                    unset($translations[$input['language']]);
                    $custom_field->update([
                        'id' => $input['field'],
                        'translations' => $translations,
                    ]);
                }
            }
        }

        return $this->prepareInput($input);
    }

    /**
     * Prepare common input for and an update.
     *
     * @param array $input
     * @return array|bool
     */
    protected function prepareInput(array $input): array|bool
    {
        $has_errors = false;

        if (array_key_exists('profiles', $input)) {
            if (!$this->validateProfileArray($input['profiles'])) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        _n('Profile', 'Profiles', Session::getPluralNumber())
                    )),
                    false,
                    ERROR
                );
                $has_errors = true;
            } else {
                $input['profiles'] = json_encode($input['profiles']);
            }
        }

        if (array_key_exists('translations', $input)) {
            if (!$this->validateTranslationsArray($input['translations'])) {
                Session::addMessageAfterRedirect(
                    htmlescape(sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        _n('Translation', 'Translations', Session::getPluralNumber())
                    )),
                    false,
                    ERROR
                );
                $has_errors = true;
            } else {
                $input['translations'] = json_encode($input['translations']);
            }
        }

        return $has_errors ? false : $input;
    }

    public function post_getFromDB()
    {
        // Clear the custom fields definitions cache when the object is reloaded
        $this->custom_field_definitions = null;
    }

    public function post_addItem()
    {
        // Register and bootstrap the definition to make it usable right now.
        $this->registerAndBootstrapDefinition();

        if ($this->isActive()) {
            $this->syncProfilesRights();
            unset($_SESSION['menu']);
        }
    }

    public function post_updateItem($history = true)
    {
        // Register and bootstrap the definition to make it usable right now.
        $this->registerAndBootstrapDefinition();

        if (in_array('is_active', $this->updates, true)) {
            // Force menu refresh when active state change
            unset($_SESSION['menu']);
        }

        if (
            in_array('is_active', $this->updates, true)
            || in_array('profiles', $this->updates, true)
            || array_key_exists('_profiles_extra', $this->input)
        ) {
            $this->syncProfilesRights();
        }
    }

    private function registerAndBootstrapDefinition(): void
    {
        $manager = static::getDefinitionManagerClass()::getInstance();
        $manager->registerDefinition($this);
        if ($this->isActive()) {
            $manager->bootstrapDefinition($this);
        }
    }

    public function cleanDBonPurge()
    {
        $this->purgeConcreteClassFromDb($this->getCustomObjectClassName());
    }

    /**
     * Delete from the database all the data related to the given concrete class.
     *
     * @param class-string<CommonDBTM> $concrete_classname
     */
    final protected function purgeConcreteClassFromDb(string $concrete_classname): void
    {
        if (!\is_a($concrete_classname, CommonDBTM::class, true)) {
            throw new LogicException();
        }

        (new $concrete_classname())->deleteByCriteria(
            [static::getForeignKeyField() => $this->getID()],
            force: true,
            history: false
        );
        (new DisplayPreference())->deleteByCriteria(['itemtype' => $concrete_classname]);
    }

    /**
     * Remove given rights from `profiles` field.
     *
     * @param int[] $rights_to_remove
     * @return void
     */
    protected function cleanRights(array $rights_to_remove): void
    {
        $profiles = $this->getDecodedProfilesField();

        foreach (array_keys($profiles) as $profile_id) {
            foreach ($rights_to_remove as $right_value) {
                $profiles[$profile_id] &= ~$right_value;
            }
        }

        $this->update(['id' => $this->getID(), 'profiles' => $profiles]);
    }

    /**
     * Set rights for given profile.
     *
     * @param int $profiles_id
     * @param int $rights
     * @return void
     */
    public function setProfileRights(int $profiles_id, int $rights): void
    {
        $profiles = $this->getDecodedProfilesField();
        $profiles[$profiles_id] = $rights;

        $this->update(
            [
                'id'       => $this->getID(),
                'profiles' => $profiles,
            ]
        );
    }

    /**
     * Synchronize `profiles` field with `ProfileRights` entries.
     *
     * @return void
     */
    protected function syncProfilesRights(): void
    {
        global $DB;

        $rightname = $this->getCustomObjectRightname();

        if (!$this->isActive()) {
            ProfileRight::deleteProfileRights([$rightname]);
        } else {
            $profiles_iterator = $DB->request([
                'SELECT' => ['id', 'interface'],
                'FROM'   => Profile::getTable(),
            ]);

            foreach ($profiles_iterator as $profile_data) {
                $profile_id = $profile_data['id'];
                $rights = $profile_data['interface'] === 'helpdesk'
                    ? 0
                    : $this->getRightsForProfile($profile_id);

                ProfileRight::updateProfileRights($profile_id, [$rightname => $rights]);
            }
        }
    }

    /**
     * Indicates whether at least one profile has rights enabled on object concrete class.
     *
     * @return bool
     */
    protected function hasRightsEnabled(): bool
    {
        if (!$this->isNewItem()) {
            $profiles = $this->getDecodedProfilesField();
            foreach ($profiles as $rights) {
                if ($rights > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return translated name.
     *
     * @param int $count
     * @return string
     */
    public function getTranslatedName(int $count = 1): string
    {
        if ($this->isNewItem()) {
            return '';
        }

        $translations = $this->getDecodedTranslationsField();
        $language = Session::getLanguage();
        $current_translation = $language !== null ? ($translations[$language] ?? null) : null;
        if ($current_translation === null) {
            return $this->fields['label'];
        }

        // retrieve the formulas associated to the language
        $gettext_language = Language::getById($language);

        // compute the formula with the paramater count
        $formula_to_compute = str_replace('n', (string) $count, $gettext_language->formula);
        $category_index_number = eval("return $formula_to_compute;");

        // retrieve the category index string (one, few, many, other) based on the index
        $found_category = $gettext_language->categories[$category_index_number] ?? $gettext_language->categories[0];
        $category_index_string = $found_category->id;

        return $current_translation[$category_index_string] ?? $this->fields['label'];
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'            => 1,
            'table'         => self::getTable(),
            'field'         => 'system_name',
            'name'          => __('System name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => 2,
            'table'         => self::getTable(),
            'field'         => 'label',
            'name'          => __('Label'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => 3,
            'table'         => self::getTable(),
            'field'         => 'is_active',
            'name'          => __('Active'),
            'datatype'      => 'bool',
        ];

        $search_options[] = [
            'id'            => 4,
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => 5,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => 6,
            'table'         => self::getTable(),
            'field'         => 'icon',
            'name'          => __('Icon'),
            'datatype'      => 'specific',
            'searchtype'    => ['equals'],
        ];

        $search_options[] = [
            'id'            => 7,
            'table'         => self::getTable(),
            'field'         => 'comment',
            'name'          => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'      => 'text',
        ];

        $search_options[] = [
            'id'            => 8,
            'table'         => self::getTable(),
            'field'         => 'translations',
            'name'          => __('Translations'),
            'datatype'      => 'specific',
        ];

        return $search_options;
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'icon':
                $value = htmlescape($values[$field]);
                return sprintf('<i class="ti %s"></i>', $value);
            case 'translations':
                $translations = json_decode($values[$field], associative: true);

                // language=Twig
                return TemplateRenderer::getInstance()->renderFromStringTemplate(
                    <<<TWIG
                    {% if translations is not empty %}
                        <ul>
                            {% for language, plurals in translations %}
                                <li>
                                    {{ config('languages')[language][0] }}:
                                    {% include "pages/admin/customobjects/plurals.html.twig" with {
                                        'plurals': plurals,
                                    } only %}
                                </li>
                            {% endfor %}
                        </ul>
                    {% endif %}
TWIG,
                    [
                        'translations' => $translations,
                    ]
                );
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'icon':
                $value = htmlescape($values[$field]);
                return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    {{ fields.dropdownWebIcons(name, value, '', {no_label: true, width: '200px'}) }}
TWIG, ['name' => $name, 'value' => $value]);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get rights for profile.
     *
     * @param int $profile_id
     * @return int
     */
    protected function getRightsForProfile(int $profile_id): int
    {
        $profiles_entries = $this->getDecodedProfilesField();
        return $profiles_entries[$profile_id] ?? 0;
    }

    /**
     * Return extra fields to append to the profiles configuration form.
     *
     * @param array[] $profile_data
     * @return string HTML string to append to the profiles configuration form.
     */
    protected function getExtraProfilesFields(array $profile_data): string
    {
        return '';
    }

    /**
     * Return the decoded value of the `translations` field.
     *
     * @return array
     */
    protected function getDecodedTranslationsField(): array
    {
        $translations = @json_decode($this->fields['translations'], associative: true);
        if (!$this->validateTranslationsArray($translations)) {
            trigger_error(
                sprintf('Invalid `translations` value (`%s`).', $this->fields['translations']),
                E_USER_WARNING
            );
            $this->fields['translations'] = '[]'; // prevent warning to be triggered on each method call
            $translations = [];
        }
        return $translations;
    }

    /**
     * Validate that the given translations array contains valid values.
     *
     * @param mixed $translations
     * @return bool
     */
    protected function validateTranslationsArray(mixed $translations): bool
    {
        global $CFG_GLPI;

        if (!is_array($translations)) {
            return false;
        }

        $is_valid = true;

        foreach ($translations as $language => $values) {
            if (!in_array($language, array_keys($CFG_GLPI['languages']), true)) {
                $is_valid = false;
                break;
            }

            $available_categories = array_map(
                fn(Language_Category $category) => $category->id,
                self::getPluralFormsForLanguage($language)
            );
            foreach ($values as $category => $translation) {
                if (!in_array($category, $available_categories, true)) {
                    $is_valid = false;
                    break 2;
                }
                if (!is_string($translation)) {
                    $is_valid = false;
                    break 2;
                }
            }
        }

        return $is_valid;
    }

    /**
     * Gel plural form list for given language.
     *
     * @param string $language
     * @return Language_Category[]
     */
    public static function getPluralFormsForLanguage(string $language): array
    {
        global $CFG_GLPI;

        // check language exists in GLPI configuration
        if (!array_key_exists($language, $CFG_GLPI['languages'])) {
            return [];
        }

        $cldrLanguage = Language_CldrData::getLanguageInfo($language);
        $cldrCategories = $cldrLanguage['categories'] ?? [];

        $languageCategories = [];
        foreach ($cldrCategories as $cldrCategoryId => $cldrFormulaAndExamples) {
            $category = new Language_Category($cldrCategoryId, $cldrFormulaAndExamples);
            $languageCategories[] = $category;
        }

        return $languageCategories;
    }

    /**
     * Return the decoded value of the `profiles` field.
     *
     * @return array
     * @phpstan-return array<int, int>
     */
    protected function getDecodedProfilesField(): array
    {
        $profiles = @json_decode($this->fields['profiles'], associative: true);
        if (!$this->validateProfileArray($profiles, false)) {
            trigger_error(
                sprintf('Invalid `profiles` value (`%s`).', $this->fields['profiles']),
                E_USER_WARNING
            );
            $this->fields['profiles'] = '[]'; // prevent warning to be triggered on each method call
            $profiles = [];
        }
        return $profiles;
    }

    /**
     * Validate that the given profiles array contains valid values.
     *
     * @param mixed $profiles
     * @param bool $check_values
     * @return bool
     */
    protected function validateProfileArray(mixed $profiles, bool $check_values = true): bool
    {
        global $DB;

        if (!is_array($profiles)) {
            return false;
        }

        $is_valid = true;

        $available_profiles = [];
        if ($check_values) {
            $profiles_iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => Profile::getTable(),
            ]);
            $available_profiles = array_column(iterator_to_array($profiles_iterator), 'id');
        }

        foreach ($profiles as $profile_id => $rights) {
            if (!is_int($profile_id) && !ctype_digit($profile_id)) {
                $is_valid = false;
                break;
            }
            if ($check_values && !in_array((int) $profile_id, $available_profiles, true)) {
                $is_valid = false;
                break;
            }
            if (!is_int($rights)) {
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
        return [];
    }
}
