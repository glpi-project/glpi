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

use Glpi\Application\View\TemplateRenderer;

/**
 * Centrally manages the configuration options across GLPI and plugins.
 * This includes global options, entity options, preferences, etc.
 */
final class ConfigManager
{
    private static self $instance;
    /** @var ConfigSection[] */
    private array $sections = [];
    /** @var ConfigOption[] */
    private array $options = [];

    private function __construct()
    {
    }

    /**
     * Initialize the sections and options arrays with the default values if they aren't already.
     * @return void
     * @note Must be called after the constructor as providers may use the singleton instance.
     */
    private function init(): void
    {
        if (!empty($this->options)) {
            return;
        }
        $this->sections = CoreConfigProvider::getInstance()->getConfigSections();
        $default_options = CoreConfigProvider::getInstance()->getConfigOptions();
        foreach ($default_options as $opt) {
            $this->registerOption($opt);
        }
    }

    /**
     * Registers a new configuration option.
     * @param ConfigOption $option
     * @return void
     * @note This is an instance method to ensure the singleton is initialized and loaded with the default options.
     *       If a plugin called this before the singleton was initialized, it could be possible for a bad plugin to overwrite a core option.
     */
    public function registerOption(ConfigOption $option): void
    {
        $full_name = $option->getFullName();
        if (isset($this->options[$full_name])) {
            trigger_error('Duplicate config option: ' . $full_name, E_USER_WARNING);
            return;
        }
        $this->options[$full_name] = $option;
    }

    /**
     * @return self The singleton instance
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * @param string $name The full internal name of the section including any parent sections
     * @return ConfigSection|null The section, if found
     */
    public function getSectionByFullName(string $name): ?ConfigSection
    {
        $match = array_filter($this->sections, static fn(ConfigSection $section) => $section->getFullName() === $name);
        return array_shift($match);
    }

    /**
     * Get a configuration option by context and name.
     * @param string $context The context of the option
     * @param string $name The name of the option
     * @return ConfigOption|null The option, if found
     */
    public function getOption(string $context, string $name): ?ConfigOption
    {
        $matches = array_filter($this->options, static fn(ConfigOption $option) => $option->getContext() === $context && $option->getName() === $name);
        return array_shift($matches);
    }

    /**
     * Get all configuration options that match the given criteria.
     * @param ConfigScope|null $scope The scope
     * @param string|null $top_level_section The top-level section key
     * @param string $context The context
     * @return array The matching options
     */
    public function getOptions(ConfigScope $scope = null, string $top_level_section = null, string $context = 'core'): array
    {
        $options = array_filter($this->options, static function (ConfigOption $option) use ($scope, $context, $top_level_section) {
            return ($scope === null || in_array($scope, $option->getScopes(), true)) &&
                ($context === 'all' || $option->getContext() === $context) &&
                ($top_level_section === null || $option->getSections()[0]->getKey() === $top_level_section);
        });
        usort($options, static fn($a, $b) => strcmp($a->getFullName(), $b->getFullName()));
        return $options;
    }

    /**
     * Get the value of the configuration option for a specific scope.
     * @param string $context The context of the option
     * @param string $name The name of the option
     * @param ConfigScope $scope The scope to get the value for
     * @param array $scope_params Additional data required to look up the option value based on the scope (e.g. Entity ID)
     * @param mixed|null $default
     * @return mixed
     */
    public function getOptionValue(string $context, string $name, ConfigScope $scope, array $scope_params = [], mixed $default = null): mixed
    {
        return $this->getOption($context, $name)?->getValue($scope, $scope_params, $default);
    }

    /**
     * Set the value of the configuration option for a specific scope.
     * @param string $context The context of the option
     * @param string $name The name of the option
     * @param ConfigScope $scope The scope to set the value for
     * @param mixed $value The value to set
     * @param array $scope_params Additional data required to look up the option to set the value for based on the scope (e.g. Entity ID)
     * @return bool True if the value was set successfully, false otherwise
     */
    public function setOptionValue(string $context, string $name, ConfigScope $scope, mixed $value, array $scope_params = []): bool
    {
        return $this->getOption($context, $name)?->setValue($scope, $value, $scope_params) ?? false;
    }

    /**
     * @param string $name The full internal name of the option including any parent sections (but without the scope)
     * @return ConfigScope[]
     */
    public function getAvailableScopes(string $name): array
    {
        return $this->options[$name]?->getScopes() ?? [];
    }

    /**
     * @param ConfigScope $scope
     * @param string $name
     * @return bool
     * @used-by 'templates/pages/setup/advconfig/config_table.html.twig'
     */
    public function isScopeAvailable(ConfigScope $scope, string $name): bool
    {
        return in_array($scope, $this->getAvailableScopes($name), true);
    }

    /**
     * Show the list of configuration options.
     * @return void
     */
    public function showAdvancedConfigList(): void
    {
        TemplateRenderer::getInstance()->display('pages/setup/advconfig/config_table.html.twig', [
            'options' => $this->getOptions(context: 'all'),
        ]);
    }

    /**
     * Show the edit form for a configuration option.
     * @param ConfigOption $option The option to edit
     * @param ConfigScope $scope The scope to edit the option for
     * @param array $scope_params Additional data required to look up the option to edit based on the scope (e.g. Entity ID)
     * @return void
     */
    public function showEditForm(ConfigOption $option, ConfigScope $scope, array $scope_params = []): void
    {
        TemplateRenderer::getInstance()->display('pages/setup/advconfig/edit_form.html.twig', [
            'option' => $option,
            'scope' => $scope,
            'scope_params' => $scope_params,
        ]);
    }
}
