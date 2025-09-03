<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

namespace Glpi\Application\View\Extension;

use Glpi\Plugin\Hooks;
use Plugin;
use Toolbox;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @since 10.0.0
 */
class PluginExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('call_plugin_hook', [$this, 'callPluginHook']),
            new TwigFunction('call_plugin_hook_func', [$this, 'callPluginHookFunction']),
            new TwigFunction('call_plugin_one_hook', [$this, 'callPluginOneHook']),
            new TwigFunction('get_plugin_web_dir', [$this, 'getPluginWebDir']),
            new TwigFunction('get_plugins_css_files', [$this, 'getPluginsCssFiles']),
            new TwigFunction('get_plugins_js_scripts_files', [$this, 'getPluginsJsScriptsFiles']),
            new TwigFunction('get_plugins_js_modules_files', [$this, 'getPluginsJsModulesFiles']),
            new TwigFunction('get_plugins_header_tags', [$this, 'getPluginsHeaderTags']),
        ];
    }

    /**
     * Call plugin hook with given params.
     *
     * @param string  $name          Hook name.
     * @param mixed   $params        Hook parameters.
     * @param bool    $return_result Indicates that the result should be returned.
     *
     * @return mixed|void
     */
    public function callPluginHook(string $name, $params = null, bool $return_result = false)
    {
        $result = Plugin::doHook($name, $params);

        if ($return_result) {
            return $result;
        }
    }

    /**
     * Call plugin hook function with given params.
     *
     * @param string  $name          Hook name.
     * @param mixed   $params        Hook parameters.
     * @param bool    $return_result Indicates that the result should be returned.
     *
     * @return mixed|void
     */
    public function callPluginHookFunction(string $name, $params = null, bool $return_result = false)
    {
        $result = Plugin::doHookFunction($name, $params);

        if ($return_result) {
            return $result;
        }
    }

    public function callPluginOneHook(string $plugin, string $name, $params = null, bool $return_result = false)
    {
        $result = Plugin::doOneHook($plugin, $name, $params);

        if ($return_result) {
            return $result;
        }
    }

    /**
     * Call Plugin::getWebDir() with given params.
     *
     * @param string  $plugin
     * @param bool    $full
     * @param bool    $use_url_base
     *
     * @return string|null
     *
     * @deprecated 11.0
     */
    public function getPluginWebDir(
        string $plugin,
        bool $full = true,
        bool $use_url_base = false
    ): ?string {
        Toolbox::deprecated('All plugins resources should be accessed from the `/plugins/` path.');
        return Plugin::getWebDir($plugin, $full, $use_url_base) ?: null;
    }

    /**
     * Returns the list of active plugins CSS files.
     *
     * @phpstan-return array<int, array{path: string, options: array{version: string}}>
     */
    public function getPluginsCssFiles(bool $is_anonymous_page): array
    {
        global $PLUGIN_HOOKS;

        $hook = $is_anonymous_page ? Hooks::ADD_CSS_ANONYMOUS_PAGE : Hooks::ADD_CSS;

        $css_files = [];
        if (isset($PLUGIN_HOOKS[$hook]) && count($PLUGIN_HOOKS[$hook])) {
            foreach ($PLUGIN_HOOKS[$hook] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }

                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $css_files[] = [
                        'path' => "/plugins/{$plugin}/{$file}",
                        'options' => [
                            'version' => $plugin_version,
                        ],
                    ];
                }
            }
        }
        return $css_files;
    }

    /**
     * Returns the list of active plugins JS scripts files.
     *
     * @phpstan-return array<int, array{path: string, options: array{version: string}}>
     */
    public function getPluginsJsScriptsFiles(bool $is_anonymous_page): array
    {
        global $PLUGIN_HOOKS;

        $hook = $is_anonymous_page ? Hooks::ADD_JAVASCRIPT_ANONYMOUS_PAGE : Hooks::ADD_JAVASCRIPT;

        $js_files = [];
        if (isset($PLUGIN_HOOKS[$hook]) && count($PLUGIN_HOOKS[$hook])) {
            foreach ($PLUGIN_HOOKS[$hook] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $js_files[] = [
                        'path' => "plugins/{$plugin}/{$file}",
                        'options' => [
                            'version' => $plugin_version,
                        ],
                    ];
                }
            }
        }
        return $js_files;
    }

    /**
     * Returns the list of active plugins JS modules files.
     *
     * @phpstan-return array<int, array{path: string, options: array{version: string}}>
     */
    public function getPluginsJsModulesFiles(bool $is_anonymous_page): array
    {
        global $PLUGIN_HOOKS;

        $hook = $is_anonymous_page ? Hooks::ADD_JAVASCRIPT_MODULE_ANONYMOUS_PAGE : Hooks::ADD_JAVASCRIPT_MODULE;

        $js_modules = [];
        if (isset($PLUGIN_HOOKS[$hook]) && count($PLUGIN_HOOKS[$hook])) {
            foreach ($PLUGIN_HOOKS[$hook] as $plugin => $files) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                $plugin_version  = Plugin::getPluginFilesVersion($plugin);

                if (!is_array($files)) {
                    $files = [$files];
                }
                foreach ($files as $file) {
                    $js_modules[] = [
                        'path' => "plugins/{$plugin}/{$file}",
                        'options' => [
                            'version' => $plugin_version,
                        ],
                    ];
                }
            }
        }
        return $js_modules;
    }

    /**
     * Returns the list of active plugins header tags.
     *
     * @phpstan-return array<int, array{tag: string, properties: array<string, string>}>
     */
    public function getPluginsHeaderTags(bool $is_anonymous_page): array
    {
        global $PLUGIN_HOOKS;

        $hook = $is_anonymous_page ? Hooks::ADD_HEADER_TAG_ANONYMOUS_PAGE : Hooks::ADD_HEADER_TAG;

        $header_tags = [];
        if (isset($PLUGIN_HOOKS[$hook]) && count($PLUGIN_HOOKS[$hook])) {
            foreach ($PLUGIN_HOOKS[$hook] as $plugin => $plugin_header_tags) {
                if (!Plugin::isPluginActive($plugin)) {
                    continue;
                }
                array_push($header_tags, ...$plugin_header_tags);
            }
        }
        return $header_tags;
    }
}
