<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Asset;

use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Gettext\Languages\CldrData as Language_CldrData;
use Gettext\Languages\Category as Language_Category;
use Gettext\Languages\Language;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Search\SearchOption;
use Profile;
use ProfileRight;
use Session;

final class AssetDefinition extends CommonDBTM
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _n('Asset definition', 'Asset definitions', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-database-cog';
    }

    public static function canCreate(): bool
    {
        // required due to usage of `config` rightname
        return static::canUpdate();
    }

    public static function canPurge(): bool
    {
        // required due to usage of `config` rightname
        return static::canUpdate();
    }

    protected function computeFriendlyName()
    {
        return $this->getTranslatedName();
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
        $this->addStandardTab(self::class, $tabs, $options);

        return $tabs;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof self) {
            $capacities_count   = 0;
            $profiles_count     = 0;
            $translations_count = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $capacities_count   = count($item->getDecodedCapacitiesField());
                $profiles_count     = count(array_filter($item->getDecodedProfilesField()));
                $translations_count = count($item->getDecodedTranslationsField());
            }
            return [
                1 => self::createTabEntry(
                    __('Capacities'),
                    $capacities_count,
                    self::class,
                    'ti ti-adjustments'
                ),
                // 2 is reserved for "Fields"
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
                    // 2 is reserved for "Fields" form
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

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $options['candel'] = false;

        $asset_count = 0;

        if (!self::isNewID($ID)) {
            // Add custom delete button that will show the number of assets using this definition and a link to their search page
            $asset_count = countElementsInTable(
                table: 'glpi_assets_assets',
                condition: [
                    'assets_assetdefinitions_id' => $ID,
                    'is_template' => 0
                ]
            );
            $options['addbuttons'] = [
                'purge' => [
                    'title' => _x('button', 'Delete permanently'),
                    'add_class' => 'btn-outline-danger',
                    'icon' => 'ti ti-trash',
                    'text' => _x('button', 'Delete permanently'),
                    'type' => 'submit',
                ]
            ];
        }

        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/main.html.twig',
            [
                'item'                  => $this,
                'params'                => $options,
                'has_rights_enabled'    => $this->hasRightsEnabled(),
                'reserved_system_names' => AssetDefinitionManager::getInstance()->getReservedAssetsSystemNames(),
                'asset_count'           => $asset_count,
            ]
        );
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
            fn (CapacityInterface $a, CapacityInterface $b) => strnatcasecmp($a->getLabel(), $b->getLabel())
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

    /**
     * Display profiles form.
     *
     * @return void
     */
    private function showProfilesForm(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $possible_rights = $this->getPossibleAssetRights();

        $profiles_data   = iterator_to_array(
            $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM'   => Profile::getTable(),
                'WHERE'  => [
                    // simplified interface is not supposed to have access to assets
                    ['NOT' => ['interface' => 'helpdesk']],
                ]
            ])
        );

        $nb_cb_per_col = array_fill_keys(
            array_keys($possible_rights),
            [
                'checked' => 0,
                'total' => count($profiles_data),
            ]
        );
        $nb_cb_per_row = [];

        $matrix_rows = [];
        foreach ($profiles_data as $profile_data) {
            $profile_id = $profile_data['id'];
            $profile_rights = $this->getRightsForProfile($profile_id);

            $checkbox_key = sprintf('profiles[%d]', $profile_id);

            $nb_cb_per_row[$checkbox_key] = [
                'checked' => 0,
                'total' => count($possible_rights),
            ];

            $row = [
                'label' => $profile_data['name'],
                'columns' => []
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
            'pages/admin/assetdefinition/profiles.html.twig',
            [
                'item'           => $this,
                'matrix_columns' => $possible_rights,
                'matrix_rows'    => $matrix_rows,
                'nb_cb_per_col'  => $nb_cb_per_col,
                'nb_cb_per_row'  => $nb_cb_per_row,
            ]
        );
    }

    /**
     * Display translation form.
     *
     * @return void
     */
    private function showTranslationForm(): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $translations = $this->getDecodedTranslationsField();
        uksort(
            $translations,
            fn (string $lang_a, string $lang_b) => strnatcasecmp($CFG_GLPI['languages'][$lang_a][0], $CFG_GLPI['languages'][$lang_b][0])
        );

        $rand = mt_rand();

        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/translations.html.twig',
            [
                'item' => $this,
                'classname' => $this->getAssetClassName(),
                'translations' => $translations,
                'languages_dropdown' => Dropdown::showLanguages('language', [
                    'display'             => false,
                    'display_emptychoice' => true,
                    'width'               => '100%',
                    'on_change'           => 'setModalLanguagePlural(this.value);',
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

        foreach (['capacities', 'profiles', 'translations'] as $json_field) {
            if (!array_key_exists($json_field, $input)) {
                // ensure default value of JSON fields will be a valid array
                $input[$json_field] = [];
            }
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

        if (isset($input['_save_translation']) && isset($input['language']) && isset($input['plurals'])) {
            $translations = $this->getDecodedTranslationsField();
            $translations[$input['language']] = $input['plurals'];
            unset($input['_save_translation'], $input['language'], $input['plurals']);
            $input['translations'] = $translations;
        }

        if (isset($input['_delete_translation']) && isset($input['language'])) {
            $translations = $this->getDecodedTranslationsField();
            unset($translations[$input['language']]);
            unset($input['_delete_translation'], $input['language']);
            $input['translations'] = $translations;
        }

        return $this->prepareInput($input);
    }

    /**
     * Prepare common input for and an update.
     *
     * @param array $input
     * @return array|bool
     */
    private function prepareInput(array $input): array|bool
    {
        $has_errors = false;

        if (array_key_exists('system_name', $input)) {
            if (!is_string($input['system_name']) || preg_match('/^[A-Za-z]+$/i', $input['system_name']) !== 1) {
                Session::addMessageAfterRedirect(
                    htmlspecialchars(sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        __('System name')
                    )),
                    false,
                    ERROR
                );
                $has_errors = true;
            } elseif (in_array($input['system_name'], AssetDefinitionManager::getInstance()->getReservedAssetsSystemNames())) {
                Session::addMessageAfterRedirect(
                    htmlspecialchars(sprintf(
                        __('The system name must not be the reserved word "%s".'),
                        $input['system_name']
                    )),
                    false,
                    ERROR
                );
                $has_errors = true;
            } elseif (preg_match('/(Model|Type)$/i', $input['system_name']) === 1) {
                Session::addMessageAfterRedirect(
                    __s('The system name must not end with the word "Model" or the word "Type".'),
                    false,
                    ERROR
                );
                $has_errors = true;
            }
        }

        if (array_key_exists('capacities', $input)) {
            if (!$this->validateCapacityArray($input['capacities'])) {
                Session::addMessageAfterRedirect(
                    htmlspecialchars(sprintf(
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

        if (array_key_exists('profiles', $input)) {
            if (!$this->validateProfileArray($input['profiles'])) {
                Session::addMessageAfterRedirect(
                    htmlspecialchars(sprintf(
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
                    htmlspecialchars(sprintf(
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

    public function post_addItem()
    {
        if ($this->isActive()) {
            $this->syncProfilesRights();

            // Force menu refresh when active state change
            unset($_SESSION['menu']);
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
        if (in_array('capacities', $this->updates)) {
            // When capabilities are removed, trigger the cleaning of data related to this capacity.
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

            $removed_capacities = array_diff($old_capacities, $new_capacities);
            $rights_to_remove = [];
            foreach ($removed_capacities as $capacity_classname) {
                $capacity = AssetDefinitionManager::getInstance()->getCapacity($capacity_classname);
                if ($capacity === null) {
                    // can be null if provided by a plugin that is no longer active
                    continue;
                }
                $capacity->onCapacityDisabled($this->getAssetClassName());
                array_push($rights_to_remove, ...$capacity->getSpecificRights());
            }

            if (count($rights_to_remove) > 0) {
                $this->cleanRights($rights_to_remove);
            }
        }

        if (in_array('is_active', $this->updates, true)) {
            // Force menu refresh when active state change
            unset($_SESSION['menu']);
        }

        if (in_array('is_active', $this->updates, true) || in_array('profiles', $this->updates, true)) {
            $this->syncProfilesRights();
        }
    }

    /**
     * Remove given rights from `profiles` field.
     *
     * @param int[] $rights_to_remove
     * @return void
     */
    private function cleanRights(array $rights_to_remove): void
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

    public function cleanDBonPurge()
    {
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
     * Synchronize `profiles` field with `ProfileRights` entries.
     *
     * @return void
     */
    private function syncProfilesRights(): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $rightname = $this->getAssetRightname();

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
     * Indicates whether at least one profile has rights enabled on asset concrete class.
     *
     * @return bool
     */
    private function hasRightsEnabled(): bool
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
        $current_translation = $translations[$language] ?? null;
        if ($current_translation === null) {
            return $this->fields['system_name'];
        }

        // retrieve the formulas associated to the language
        $gettext_language = Language::getById($language);

        // compute the formula with the paramater count
        $formula_to_compute = str_replace('n', $count, $gettext_language->formula);
        $category_index_number = eval("return $formula_to_compute;");

        // retrieve the category index string (one, few, many, other) based on the index
        $found_category = $gettext_language->categories[$category_index_number] ?? $gettext_language->categories[0];
        $category_index_string = $found_category->id;

        return $current_translation[$category_index_string] ?? $this->fields['system_name'];
    }

    /**
     * Return icon to use for assets.
     *
     * @return string
     */
    public function getAssetsIcon(): string
    {
        return 'ti ' . ($this->fields['icon'] ?: 'ti-box');
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
            'id'            => 3,
            'table'         => self::getTable(),
            'field'         => 'is_active',
            'name'          => __('Active'),
            'datatype'      => 'bool'
        ];

        $search_options[] = [
            'id'            => 4,
            'table'         => self::getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        $search_options[] = [
            'id'            => 5,
            'table'         => self::getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false
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
            'name'          => __('Comments'),
            'datatype'      => 'text'
        ];

        $search_options[] = [
            'id'            => 8,
            'table'         => self::getTable(),
            'field'         => 'translations',
            'name'          => __('Translations'),
            'datatype'      => 'specific'
        ];

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

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'icon':
                $value = htmlspecialchars($values[$field]);
                return sprintf('<i class="ti %s"></i>', $value);
            case 'translations':
                $translations = json_decode($values[$field], associative: true);

                return TemplateRenderer::getInstance()->renderFromStringTemplate(
                    <<<TWIG
                    {% if translations is not empty %}
                        <ul>
                            {% for language, plurals in translations %}
                                <li>
                                    {{ config('languages')[language][0] }}:
                                    {% include "pages/admin/assetdefinition/plurals.html.twig" with {
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
                $value = htmlspecialchars($values[$field] ?? '');
                return TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                    {% import 'components/form/fields_macros.html.twig' as fields %}
                    {{ fields.dropdownWebIcons(name, value, '', {no_label: true, width: '200px'}) }}
TWIG, ['name' => $name, 'value' => $value]);
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get the definition's concrete asset class name.
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getAssetClassName(bool $with_namespace = true): string
    {
        $classname = $this->fields['system_name'];

        if ($with_namespace) {
            $classname = 'Glpi\\CustomAsset\\' . $classname;
        }

        return $classname;
    }

    /**
     * Get the definition's concrete asset model class name.
     *
     * @param bool $with_namespace
     * @return string
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
     */
    public function getAssetTypeClassName(bool $with_namespace = true): string
    {
        return $this->getAssetClassName($with_namespace) . 'Type';
    }

    /**
     * Get the definition's concrete asset rightname.
     *
     * @param AssetDefinition $definition
     * @return string
     */
    public function getAssetRightname(): string
    {
        return sprintf('asset_%s', strtolower($this->fields['system_name']));
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
     * Get the list of possible rights for the assets.
     * @return array
     */
    private function getPossibleAssetRights(): array
    {
        $class = $this->getAssetClassName();
        $object = new $class();
        return $object->getRights();
    }

    /**
     * Get rights for profile.
     *
     * @param int $profile_id
     * @return int
     */
    private function getRightsForProfile(int $profile_id): int
    {
        $profiles_entries = $this->getDecodedProfilesField();
        return $profiles_entries[$profile_id] ?? 0;
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
     * Return the decoded value of the `translations` field.
     *
     * @return array
     */
    private function getDecodedTranslationsField(): array
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
     * @param mixed $profiles
     * @return bool
     */
    private function validateTranslationsArray(mixed $translations): bool
    {
        /** @var array $CFG_GLPI */
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
                fn (Language_Category $category) => $category->id,
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
     * @return \Gettext\Languages\Category[]
     */
    public static function getPluralFormsForLanguage(string $language): array
    {
        /** @var array $CFG_GLPI */
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
    private function getDecodedProfilesField(): array
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
    private function validateProfileArray(mixed $profiles, bool $check_values = true): bool
    {
        /** @var \DBmysql $DB */
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
            if ($check_values && !in_array((int)$profile_id, $available_profiles, true)) {
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
}
