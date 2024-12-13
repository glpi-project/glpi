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

namespace Glpi\Config;

use Entity;

/**
 * A configuration option
 */
abstract class ConfigOption
{
    /**
     * @param ConfigScope[] $scopes The valid scopes
     * @param ConfigSection $section The configuration section this option belongs to
     * @param string $name The internal name of the option
     * @param string $label The label of the option
     * @param InputType $type The type of the option
     * @param array $type_options The options for the type of the option
     * @param string $context The context of the option
     */
    public function __construct(
        private readonly array $scopes,
        private readonly ConfigSection $section,
        private readonly string $name,
        private readonly string $label,
        private readonly InputType $type,
        private readonly array $type_options = [],
        private readonly string $context = 'core',
    ) {
        if (empty($scopes)) {
            throw new \InvalidArgumentException('At least one scope must be provided');
        }
    }

    /**
     * @return ConfigScope[] The scopes available for the option
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @return ConfigSection The most immediate section of the option (it may be a child of a parent section)
     */
    public function getSection(): ConfigSection
    {
        return $this->section;
    }

    /**
     * @return ConfigSection[] The full path of sections to the option
     */
    public function getSections(): array
    {
        $sections = [];
        $section = $this->section;
        while ($section !== null) {
            $sections[] = $section;
            $section = $section->getParent();
        }
        return array_reverse($sections);
    }

    /**
     * @return string The internal name of the option
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string The label of the option
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string The internal name of the option (context.name)
     */
    public function getFullName(): string
    {
        return $this->getContext() . '.' . $this->getName();
    }

    /**
     * @return InputType The type of the option
     */
    public function getType(): InputType
    {
        return $this->type;
    }

    /**
     * @return array The options for the type of the option
     */
    public function getTypeOptions(): array
    {
        return $this->type_options;
    }

    /**
     * @return string The context of the option
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Get the value of the configuration option for a specific scope.
     * @param ConfigScope $scope The scope to get the value for
     * @param mixed|null $default The default value to return if the option value is not found
     * @param array $scope_params The parameters to use when looking up the option value
     * @phpstan-param array{context?: string, entities_id?: int, users_id?: int} $scope_params
     * @return mixed
     */
    public function getValue(ConfigScope $scope, mixed $default = null, array $scope_params = []): mixed
    {
        if (!in_array($scope, $this->scopes, true)) {
            return $default;
        }
        $fn_format_loaded_value = function ($value) {
            if (isset($this->type_options['multiple']) && $this->type_options['multiple']) {
                return importArrayFromDB($value);
            }
            return $value;
        };
        switch ($scope) {
            case ConfigScope::GLOBAL:
                return $fn_format_loaded_value(\Config::getConfigurationValue($this->context, $this->getName())) ?? $default;
            case ConfigScope::ENTITY:
                if (!isset($scope_params['entities_id'])) {
                    trigger_error('No entities_id provided for ConfigScope::ENTITY', E_USER_ERROR);
                }
                $entity = new \Entity();
                if (!$entity->getFromDB($scope_params['entities_id'])) {
                    return $default;
                }
                return $fn_format_loaded_value($entity->fields[$this->getName()]) ?? $default;
            case ConfigScope::USER:
                if (!isset($scope_params['users_id'])) {
                    trigger_error('No users_id provided for ConfigScope::USER', E_USER_ERROR);
                }
                $user = new \User();
                if (!$user->getFromDB($scope_params['users_id'])) {
                    return $default;
                }
                return $fn_format_loaded_value($user->fields[$this->getName()]) ?? $default;
        }
        return $default;
    }

    /**
     * Set the value of the configuration option for a specific scope.
     * @param ConfigScope $scope The scope to set the value for
     * @param mixed $value The value to set
     * @param array $scope_params The parameters to use when looking up where to set the value based on the scope (e.g. Entity ID)
     * @return bool True if the value was set successfully, false otherwise
     */
    public function setValue(ConfigScope $scope, mixed $value, array $scope_params = []): bool
    {
        if (!in_array($scope, $this->scopes, true)) {
            return false;
        }
        if (is_array($value)) {
            $value = exportArrayToDB($value);
        }
        switch ($scope) {
            case ConfigScope::GLOBAL:
                \Config::setConfigurationValues($this->context, [$this->getName() => $value]);
                // No clue if it worked. Assume it did. The previous method call should be replaced in future improvements to this feature anyway.
                return true;
            case ConfigScope::ENTITY:
                if (!isset($scope_params['entities_id'])) {
                    trigger_error('No entities_id provided for ConfigScope::ENTITY', E_USER_ERROR);
                }
                $entity = new \Entity();
                if ($entity->getFromDB($scope_params['entities_id'])) {
                    return $entity->update([
                        $this->getName() => $value,
                    ]);
                }
                break;
            case ConfigScope::USER:
                if (!isset($scope_params['users_id'])) {
                    trigger_error('No users_id provided for ConfigScope::USER', E_USER_ERROR);
                }
                $user = new \User();
                if ($user->getFromDB($scope_params['users_id'])) {
                    return $user->update([
                        'id' => $user->getID(),
                        $this->getName() => $value,
                    ]);
                }
                break;
        }
        return false;
    }

    /**
     * Get the computed value of the configuration option for the given scope.
     * For example, if this option exists in the {@link ConfigScope::GLOBAL} and {@link ConfigScope::USER} scopes, and
     * is set at the global scope but not the user scope, this will return the global value. If it is set at both
     * scopes, the user value will be returned.
     * The order of precedence is determined by the weight of the scope.
     * @param $scope_params
     * @param mixed|null $default
     * @return mixed
     * @see ConfigScope::getWeight()
     */
    public function getComputedValue($scope_params = [], mixed $default = null): mixed
    {
        $scopes = $this->getScopes();
        // Sort by highest weight first. We will work from most specific to least specific until we find a value.
        usort($scopes, static fn(ConfigScope $a, ConfigScope $b) => $b->getWeight() <=> $a->getWeight());
        foreach ($scopes as $scope) {
            if ($scope === ConfigScope::ENTITY) {
                if (!isset($scope_params['entities_id'])) {
                    trigger_error('No entities_id provided for ConfigScope::ENTITY', E_USER_ERROR);
                }
                return Entity::getUsedConfig(
                    fieldref: $scope_params['reference_field'] ?? $this->getName(),
                    entities_id: $scope_params['entities_id'],
                    fieldval: $this->getName(),
                    default_value: $default,
                );
            }
            $value = $this->getValue(scope: $scope, scope_params: $scope_params);
            if ($value !== null) {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Formats the given raw value into a display value depending on this option's type.
     * @param mixed $raw_value The raw value to format
     * @return mixed The formatted value
     * @see InputType::getValue()
     * @see InputType::getComputedValue()
     * @used-by 'templates/pages/setup/advconfig/config_table.html.twig'
     */
    public function getDisplayValue(mixed $raw_value): mixed
    {
        return $raw_value ?? null;
    }

    abstract public function renderInput(ConfigScope $scope, array $scope_params = [], array $input_params = []): void;
}
