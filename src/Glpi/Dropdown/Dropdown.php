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

use CommonTreeDropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\CustomObject\AbstractDefinition;
use Glpi\CustomObject\CustomObjectTrait;
use RuntimeException;

abstract class Dropdown extends CommonTreeDropdown
{
    use CustomObjectTrait;

    /**
     * Dropdown definition system name.
     *
     * Must be defined here to make PHPStan happy (see https://github.com/phpstan/phpstan/issues/8808).
     * Must be defined by child class too to ensure that assigning a value to this property will affect
     * each child classe independently.
     */
    protected static string $definition_system_name;

    public static function canView(): bool
    {
        if (!parent::canView()) {
            return false;
        }
        return (bool) static::getDefinition()->fields['is_active'];
    }

    /**
     * Get the dropdown definition related to concrete class.
     *
     * @return DropdownDefinition
     */
    public static function getDefinition(): DropdownDefinition
    {
        $definition = DropdownDefinitionManager::getInstance()->getDefinition(static::$definition_system_name);
        if (!($definition instanceof DropdownDefinition)) {
            throw new RuntimeException('Dropdown definition is expected to be defined in concrete class.');
        }

        return $definition;
    }

    /**
     * Get the definition class instance.
     */
    public static function getDefinitionClassInstance(): AbstractDefinition
    {
        return new DropdownDefinition();
    }

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        if ($input === false) {
            return false;
        }
        return $this->prepareDefinitionInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        if ($input === false) {
            return false;
        }

        return $this->prepareDefinitionInput($input);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            'pages/setup/custom_dropdown.html.twig',
            [
                'item'   => $this,
                'params' => $options,
                'additional_fields' => $this->getAdditionalFields(),
            ]
        );
        return true;
    }

    public function rawSearchOptions()
    {
        $search_options = parent::rawSearchOptions();

        return $this->amendSearchOptions($search_options);
    }
}
