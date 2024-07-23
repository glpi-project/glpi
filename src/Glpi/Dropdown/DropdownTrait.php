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

use Glpi\Application\View\TemplateRenderer;
use Toolbox;

trait DropdownTrait
{
    public static function getTypeName($nb = 0)
    {
        return static::getDefinition()->getTranslatedName($nb);
    }

    public static function getIcon()
    {
        return static::getDefinition()->getCustomObjectIcon();
    }

    public static function getTable($classname = null)
    {
        if (is_a($classname ?? static::class, self::class, true)) {
            return parent::getTable(Dropdown::class);
        }
        return parent::getTable($classname);
    }

    public static function getSearchURL($full = true)
    {
        return Toolbox::getItemTypeSearchURL(Dropdown::class, $full)
            . '?class=' . static::getDefinition()->getCustomObjectClassName(false);
    }

    public static function getFormURL($full = true)
    {
        return Toolbox::getItemTypeFormURL(Dropdown::class, $full)
            . '?class=' . static::getDefinition()->getCustomObjectClassName(false);
    }

    public static function getById(?int $id)
    {
        if ($id === null) {
            return false;
        }

        // Load the asset definition corresponding to given asset ID
        $definition_request = [
            'INNER JOIN' => [
                Dropdown::getTable()  => [
                    'ON'  => [
                        Dropdown::getTable()            => DropdownDefinition::getForeignKeyField(),
                        DropdownDefinition::getTable() => DropdownDefinition::getIndexName(),
                    ]
                ],
            ],
            'WHERE' => [
                Dropdown::getTableField(Dropdown::getIndexName()) => $id,
            ],
        ];
        $definition = new DropdownDefinition();
        if (!$definition->getFromDBByRequest($definition_request)) {
            return false;
        }

        // Instanciate concrete class
        $dropdown_class = $definition->getCustomObjectClassName();
        $dropdown = new $dropdown_class();
        if (!$dropdown->getFromDB($id)) {
            return false;
        }

        return $dropdown;
    }

    public static function getSystemSQLCriteria(?string $tablename = null): array
    {
        $table_prefix = $tablename !== null
            ? $tablename . '.'
            : '';

        // Keep only items from current definition must be shown.
        $criteria = [
            $table_prefix . DropdownDefinition::getForeignKeyField() => static::getDefinition()->getID(),
        ];

        // Add another layer to the array to prevent losing duplicates keys if the
        // result of the function is merged with another array.
        $criteria = [crc32(serialize($criteria)) => $criteria];

        return $criteria;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            'pages/setup/custom_dropdown.html.twig',
            [
                'item'   => $this,
                'params' => $options,
                'additional_fields' => $this->getAdditionalFields()
            ]
        );
        return true;
    }

    private function handleTreeFields(array $input): array
    {
        if (!static::getDefinition()->fields['is_tree']) {
            // Ensure `completename` is set when not a tree dropdown so it works properly if the definition is changed to be a tree dropdown later.
            $input['completename'] = $this->isNewItem() ? $input['name'] : ($input['name'] ?? $this->fields['name']);
            // Block setting the other tree fields
            unset($input['level'], $input['ancestors_cache'], $input['sons_cache']);
        }
        return $input;
    }

    public function prepareInputForAdd($input)
    {
        if (empty($input['name'])) {
            \Session::addMessageAfterRedirect(__('A name is required.'), false, ERROR);
            return false;
        }
        $this->handleTreeFields($input);
        $input = parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareDefinitionInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        if (empty($input['name'])) {
            unset($input['name']);
        }
        $this->handleTreeFields($input);
        $input = parent::prepareInputForUpdate($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareDefinitionInput($input);
    }

    /**
     * Ensure definition input corresponds to the current concrete class.
     *
     * @param array $input
     * @return array
     */
    private function prepareDefinitionInput(array $input): array
    {
        $definition_fkey = DropdownDefinition::getForeignKeyField();
        $definition_id   = static::getDefinition()->getID();

        if (
            array_key_exists($definition_fkey, $input)
            && (int)$input[$definition_fkey] !== $definition_id
        ) {
            throw new \RuntimeException('Dropdown definition does not match the current concrete class.');
        }

        if (
            !$this->isNewItem()
            && (int)$this->fields[$definition_fkey] !== $definition_id
        ) {
            throw new \RuntimeException('Dropdown definition cannot be changed.');
        }

        $input[$definition_fkey] = $definition_id;

        return $input;
    }

    public function rawSearchOptions()
    {
        $opts = parent::rawSearchOptions();

        // Ensure the search engine can handle search options when multiple classes map to the same table
        foreach ($opts as &$search_option) {
            if (
                is_array($search_option)
                && array_key_exists('table', $search_option)
                && $search_option['table'] === static::getTable()
            ) {
                // Search class could not be able to retrieve the concrete class when using `getItemTypeForTable()`,
                // so we have to define an `itemtype` here.
                $search_option['itemtype'] = static::class;
            }
        }

        return $opts;
    }
}
