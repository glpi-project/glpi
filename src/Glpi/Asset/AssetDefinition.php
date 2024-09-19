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

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Capacity\CapacityInterface;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Search\SearchOption;
use Session;

/**
 * @extends AbstractDefinition<\Glpi\Asset\Asset>
 */
final class AssetDefinition extends AbstractDefinition
{
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

    public function prepareInputForAdd($input)
    {
        foreach (['capacities', 'profiles', 'translations'] as $json_field) {
            if (!array_key_exists($json_field, $input)) {
                // ensure default value of JSON fields will be a valid array
                $input[$json_field] = [];
            }
        }
        return parent::prepareInputForAdd($input);
    }

    protected function prepareInput(array $input): array|bool
    {
        $has_errors = false;

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

        return $has_errors ? false : parent::prepareInput($input);
    }

    public function post_addItem()
    {
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

        parent::post_addItem();
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

        parent::post_updateItem();
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
}
