<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
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

    public static function canCreate()
    {
        // required due to usage of `config` rightname
        return static::canUpdate();
    }

    public static function canPurge()
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
            return [
                1 => self::createTabEntry(
                    __('Capacities'),
                    0,
                    self::class,
                    'ti ti-adjustments'
                ),
                // 2 is reserved for "Fields"
                3 => self::createTabEntry(
                    _n('Profile', 'Profiles', Session::getPluralNumber()),
                    0,
                    self::class,
                    'ti ti-user-check'
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
            }
        }
        return true;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/main.html.twig',
            [
                'item'               => $this,
                'params'             => $options,
                'has_rights_enabled' => $this->hasRightsEnabled(),
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

        TemplateRenderer::getInstance()->display(
            'pages/admin/assetdefinition/capacities.html.twig',
            [
                'item' => $this,
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

    public function prepareInputForAdd($input)
    {
        if (!array_key_exists('system_name', $input)) {
            Session::addMessageAfterRedirect(
                __('The system name is mandatory.'),
                false,
                ERROR
            );
            return false;
        }

        foreach (['capacities', 'profiles'] as $json_field) {
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
                __('The system name cannot be changed.'),
                false,
                ERROR
            );
            return false;
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
        if (
            array_key_exists('system_name', $input)
            && (!is_string($input['system_name']) || preg_match('/^[a-z]+$/i', $input['system_name']) !== 1)
        ) {
            Session::addMessageAfterRedirect(
                sprintf(
                    __('The following field has an incorrect value: "%s".'),
                    __('System name')
                ),
                false,
                ERROR
            );
            return false;
        }

        if (array_key_exists('capacities', $input)) {
            if (!$this->validateCapacityArray($input['capacities'])) {
                Session::addMessageAfterRedirect(
                    sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        __('Capacities')
                    ),
                    false,
                    ERROR
                );
                return false;
            }
            $input['capacities'] = json_encode($input['capacities']);
        }

        if (array_key_exists('profiles', $input)) {
            if (!$this->validateProfileArray($input['profiles'])) {
                Session::addMessageAfterRedirect(
                    sprintf(
                        __('The following field has an incorrect value: "%s".'),
                        _n('Profile', 'Profiles', Session::getPluralNumber())
                    ),
                    false,
                    ERROR
                );
                return false;
            }
            $input['profiles'] = json_encode($input['profiles']);
        }

        return $input;
    }

    public function post_addItem()
    {
        if ($this->isActive()) {
            $this->syncProfilesRights();

            // Force menu refresh when active state change
            unset($_SESSION['menu']);
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
                $capacity->onCapacityDisabled($this->getConcreteClassName());
                array_push($rights_to_remove, ...$capacity->getSpecificRights());
            }

            if (count($rights_to_remove) > 0) {
                $this->cleanRights($rights_to_remove);
            }
        }

        if (in_array('is_active', $this->updates)) {
            // Force menu refresh when active state change
            unset($_SESSION['menu']);
        }

        if (
            in_array('is_active', $this->updates)
            || ($this->isActive() && in_array('profiles', $this->updates))
        ) {
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
                unset($profiles[$profile_id][$right_value]);
            }
        }

        $this->update(['id' => $this->getID(), 'profiles' => $profiles]);
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
            foreach ($profiles as $rights_matrix) {
                foreach ($rights_matrix as $enabled) {
                    if ((bool)$enabled) {
                        return true;
                    }
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
        // TODO Return translated plural form.
        return $this->fields['system_name'];
    }

    /**
     * Return icon to use for assets.
     *
     * @return string
     */
    public function getAssetsIcon(): string
    {
        return $this->fields['icon'] ?: 'ti ti-box';
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        $search_options[] = [
            'id'            => 1,
            'table'         => $this->getTable(),
            'field'         => 'system_name',
            'name'          => __('System name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $search_options[] = [
            'id'            => 3,
            'table'         => $this->getTable(),
            'field'         => 'is_active',
            'name'          => __('Active'),
            'datatype'      => 'bool'
        ];

        $search_options[] = [
            'id'            => 4,
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        $search_options[] = [
            'id'            => 5,
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false
        ];

        $search_options[] = [
            'id'            => 6,
            'table'         => $this->getTable(),
            'field'         => 'icon',
            'name'          => __('Icon'),
            'datatype'      => 'specific',
        ];

        $search_options[] = [
            'id'            => 7,
            'table'         => $this->getTable(),
            'field'         => 'comment',
            'name'          => __('Comments'),
            'datatype'      => 'text'
        ];

        $i = 1000;
        $search_options[] = [
            'id'   => 'capacities',
            'name' => __('Capacities')
        ];
        foreach (AssetDefinitionManager::getInstance()->getAvailableCapacities() as $capacity) {
            $i++;

            // capacity is stored in a JSON array, so entry is surrounded by double quotes
            $search_string = json_encode($capacity::class);
            // Backslashes must be doubled in LIKE clause, according to MySQL documentation:
            // > To search for \, specify it as \\\\; this is because the backslashes are stripped
            // > once by the parser and again when the pattern match is made,
            // > leaving a single backslash to be matched against.
            $search_string = str_replace('\\', '\\\\', $search_string);

            $search_options[] = [
                'id'            => $i,
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
        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'icon':
                $value = htmlspecialchars($values[$field]);
                return sprintf('<i class="%s"></i>', $value);
                break;
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
                // TODO Show icon selector
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * Get the definition's concrete asset class name.
     *
     * @param bool $with_namespace
     * @return string
     */
    public function getConcreteClassName(bool $with_namespace = true): string
    {
        $classname = $this->fields['system_name'];

        if ($with_namespace) {
            $classname = 'Glpi\\CustomAsset\\' . $classname;
        }

        return $classname;
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
        $class = $this->getConcreteClassName();
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

        $rights = 0;

        foreach ($profiles_entries as $key => $rights_matrix) {
            if ((int)$key === $profile_id) {
                foreach ($rights_matrix as $right_value => $is_enabled) {
                    if ((bool)$is_enabled) {
                        $rights += $right_value;
                    }
                }
                break;
            }
        }

        return $rights;
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
     * Return the decoded value of the `profiles` field.
     *
     * @return array
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

        foreach ($profiles as $profile_id => $rights_matrix) {
            if (!is_int($profile_id) && !ctype_digit($profile_id)) {
                $is_valid = false;
                break;
            }
            if ($check_values && !in_array((int)$profile_id, $available_profiles, true)) {
                $is_valid = false;
                break;
            }
            foreach ($rights_matrix as $right_value => $is_enabled) {
                if (
                    !filter_var($right_value, FILTER_VALIDATE_INT)
                    || filter_var($is_enabled, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === null
                ) {
                    $is_valid = false;
                    break;
                }
            }
        }

        return $is_valid;
    }
}
