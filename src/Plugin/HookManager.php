<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Plugin;

class HookManager
{
    protected string $plugin;

    public function __construct(string $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * Enable CSRF
     */
    public function enableCSRF(): void
    {
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT][$this->plugin] = true;
    }

    /**
     * Add a given javascript file
     *
     * @param string $file
     */
    public function registerJavascriptFile(string $file): void
    {
        $this->registerFile(Hooks::ADD_JAVASCRIPT, $file);
    }

    /**
     * Add a given CSS file
     *
     * @param string $file
     */
    public function registerCSSFile(string $file): void
    {
        $this->registerFile(Hooks::ADD_CSS, $file);
    }

    /**
     * Add a given file for the given hook
     *
     * @param string $hook
     * @param string $file
     */
    protected function registerFile(string $hook, string $file): void
    {
        global $PLUGIN_HOOKS;

        // Check if the given hook is a valid file hook
        $allowed_file_hooks = Hooks::getFileHooks();
        if (!in_array($hook, $allowed_file_hooks)) {
            trigger_error("Invalid file hook: '$hook'", E_USER_ERROR);
        }

        // Init target array if needed
        if (!isset($PLUGIN_HOOKS[$hook][$this->plugin])) {
            $PLUGIN_HOOKS[$hook][$this->plugin] = [];
        }

        // Register file
        $PLUGIN_HOOKS[$hook][$this->plugin][] = $file;
    }

    /**
     * Add a functionnal hook
     *
     * @param string $hook
     * @param string $file
     */
    public function registerFunctionalHook(
        string $hook,
        callable $function
    ): void {
        global $PLUGIN_HOOKS;

        // Check if the given hook is a valid functionnal hook
        $allowed_file_hooks = Hooks::getFunctionalHooks();
        if (!in_array($hook, $allowed_file_hooks)) {
            trigger_error("Invalid functional hook: '$hook'", E_USER_ERROR);
        }

        $PLUGIN_HOOKS[$hook][$this->plugin] = $function;
    }

    /**
     * Add an item hook
     *
     * @param string $hook
     * @param string $itemtype
     * @param string $file
     */
    public function registerItemHook(
        string $hook,
        string $itemtype,
        callable $function
    ): void {
        global $PLUGIN_HOOKS;

        // Check if the given hook is a valid item hook
        $allowed_file_hooks = Hooks::getItemHooks();
        if (!in_array($hook, $allowed_file_hooks)) {
            trigger_error("Invalid item hook: '$hook'", E_USER_ERROR);
        }

        $PLUGIN_HOOKS[$hook][$this->plugin][$itemtype] = $function;
    }

    /**
     * Register fields that need to be encrypted
     *
     * @param array $fields array of table.field
     */
    public function registerSecureFields(array $fields): void
    {
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS[Hooks::SECURED_FIELDS][$this->plugin] = $fields;
    }

    /**
     * Register configuration values that need to be encrypted
     *
     * @param array $configs
     */
    public function registerSecureConfigs(array $configs): void
    {
        global $PLUGIN_HOOKS;

        $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS][$this->plugin] = $configs;
    }
}
