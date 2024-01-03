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

namespace Glpi\Application\View\Extension;

use Plugin;
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
            new TwigFunction('get_plugin_web_dir', [$this, 'getPluginWebDir']),
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

    /**
     * Call Plugin::getWebDir() with given params.
     *
     * @param string  $plugin
     * @param bool    $full
     * @param bool    $use_url_base
     *
     * @return string|null
     */
    public function getPluginWebDir(
        string $plugin,
        bool $full = true,
        bool $use_url_base = false
    ): ?string {
        return Plugin::getWebDir($plugin, $full, $use_url_base) ?: null;
    }
}
