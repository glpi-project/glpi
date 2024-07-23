<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Glpi\CustomObject\AbstractDefinition;
use Session;

final class DropdownDefinition extends AbstractDefinition
{
    public static function getTypeName($nb = 0)
    {
        return _n('Dropdown definition', 'Dropdown definitions', $nb);
    }

    public static function getCustomObjectBaseClass(): string
    {
        return Dropdown::class;
    }

    public static function getCustomObjectNamespace(): string
    {
        return 'Glpi\\CustomDropdown';
    }

    public static function getDefinitionManagerClass(): string
    {
        return DropdownDefinitionManager::class;
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
                    // 2 is reserved for "Fields" form
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

    public function post_updateItem($history = true)
    {
        /**
         * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
         * @var \DBmysql $DB
         */
        global $GLPI_CACHE, $DB;

        parent::post_updateItem($history);

        if (isset($this->input['is_tree']) && $this->input['is_tree'] && !$this->oldvalues['is_tree']) {
            // If switching from non-tree to tree dropdown, the related caches should be cleared (only an issue if it was a tree dropdown at some point before)
            $it = $DB->request([
                'SELECT' => ['id'],
                'FROM' => 'glpi_dropdowns_dropdowns',
                'WHERE' => ['dropdowns_dropdowndefinitions_id' => $this->getID()],
            ]);
            foreach ($it as $data) {
                $GLPI_CACHE->delete('ancestors_cache_glpi_dropdowns_dropdowns_' . $data['id']);
                $GLPI_CACHE->delete('sons_cache_glpi_dropdowns_dropdowns_' . $data['id']);
            }
            $DB->update(
                'glpi_dropdowns_dropdowns',
                [
                    'ancestors_cache' => null,
                    'sons_cache' => null,
                ],
                ['dropdowns_dropdowndefinitions_id' => $this->getID()]
            );
        }
    }

    public function cleanDBonPurge()
    {
        $related_classes = [
            $this->getCustomObjectClassName(),
        ];
        foreach ($related_classes as $classname) {
            (new $classname())->deleteByCriteria(
                ['dropdowns_dropdowndefinitions_id' => $this->getID()],
                force: true,
                history: false
            );
        }
    }

    protected function displayMainForm(int $item_count, $options = []): void
    {
        $definition_manager = self::getDefinitionManagerClass()::getInstance();
        TemplateRenderer::getInstance()->display(
            'pages/admin/dropdowndefinition/main.html.twig',
            [
                'item'                  => $this,
                'params'                => $options,
                'has_rights_enabled'    => $this->hasRightsEnabled(),
                'reserved_system_names' => $definition_manager->getReservedSystemNames(),
                'item_count'           => $item_count,
            ]
        );
    }
}
