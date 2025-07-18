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

namespace Glpi\Dropdown;

use CommonDropdown;
use CommonGLPI;
use DisplayPreference;
use Glpi\CustomObject\AbstractDefinition;
use Session;

/**
 * @extends AbstractDefinition<Dropdown>
 */
final class DropdownDefinition extends AbstractDefinition
{
    public static function getTypeName($nb = 0)
    {
        return _n('Dropdown definition', 'Dropdown definitions', $nb);
    }

    public static function getSectorizedDetails(): array
    {
        return ['config', CommonDropdown::class, self::class];
    }

    public static function getCustomObjectBaseClass(): string
    {
        return Dropdown::class;
    }

    public static function getCustomObjectNamespace(): string
    {
        return 'Glpi\\CustomDropdown';
    }

    public static function getCustomObjectClassSuffix(): string
    {
        return 'Dropdown';
    }

    public static function getDefinitionManagerClass(): string
    {
        return DropdownDefinitionManager::class;
    }

    /**
     * Get the definition's concrete dropdown class name.
     *
     * @param bool $with_namespace
     * @return string
     * @phpstan-return class-string<Dropdown>
     */
    public function getDropdownClassName(bool $with_namespace = true): string
    {
        return $this->getCustomObjectClassName($with_namespace);
    }

    public function getCustomObjectRightname(): string
    {
        return sprintf('dropdown_%s', strtolower($this->fields['system_name']));
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
            $profiles_count     = 0;
            $translations_count = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $profiles_count     = count(array_filter($item->getDecodedProfilesField()));
                $translations_count = count($item->getDecodedTranslationsField());
            }
            return [
                // 2 is reserved for "Fields"
                2 => self::createTabEntry(
                    _n('Profile', 'Profiles', Session::getPluralNumber()),
                    $profiles_count,
                    self::class,
                    'ti ti-user-check'
                ),
                3 => self::createTabEntry(
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
                    // 1 is reserved for "Fields" form
                    break;
                case 2:
                    $item->showProfilesForm();
                    break;
                case 3:
                    $item->showTranslationForm();
                    break;
            }
        }
        return true;
    }

    public function prepareInputForAdd($input)
    {
        foreach (['profiles', 'translations'] as $json_field) {
            if (!array_key_exists($json_field, $input)) {
                // ensure default value of JSON fields will be a valid array
                $input[$json_field] = [];
            }
        }
        return parent::prepareInputForAdd($input);
    }

    public function post_addItem()
    {
        parent::post_addItem();

        // Add default display preferences for the new definition
        $prefs = [
            14, // Name
        ];
        $pref = new DisplayPreference();
        foreach ($prefs as $field) {
            $pref->add([
                'itemtype' => $this->getDropdownClassName(),
                'num'      => $field,
                'users_id' => 0,
            ]);
        }
    }
}
