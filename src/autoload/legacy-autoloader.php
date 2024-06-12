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

/**
 * Classes loader
 *
 * @param string $classname : class to load
 *
 * @return void|boolean
 */
function glpi_autoload($classname)
{
    $plug = isPluginItemType($classname);
    if (!$plug) {
        // PSR-4 styled autoloading for classes without namespace
        $path = sprintf('%s/src/%s.php', dirname(__FILE__, 3), $classname);
        if (strpos($classname, NS_GLPI) !== 0 && file_exists($path)) {
            include_once($path);
        }
        return;
    }

    $plugin_name  = $plug['plugin'];
    $plugin_key   = strtolower($plugin_name);
    $plugin_class = $plug['class'];

    if (!Plugin::isPluginLoaded($plugin_key)) {
        return false;
    }

    $plugin_path = null;
    foreach (PLUGINS_DIRECTORIES as $plugins_dir) {
        $dir_to_check = sprintf('%s/%s', $plugins_dir, $plugin_key);
        if (is_dir($dir_to_check)) {
            $plugin_path = $dir_to_check;
            break;
        }
    }

    // Legacy class path, e.g. `MyPluginFoo` -> `plugins/myplugin/inc/foo.class.php`
    $legacy_path          = sprintf('%s/inc/%s.class.php', $plugin_path, str_replace('\\', '/', strtolower($plugin_class)));
    // PSR-4 styled path for class without namespace, e.g. `MyPluginFoo` -> `plugins/myplugin/src/MyPluginFoo.php`
    $psr4_styled_path     = sprintf('%s/src/%s.php', $plugin_path, str_replace('\\', '/', $classname));
    // PSR-4 unprefixed path for class with namespace, e.g. `GlpiPlugin\\MyPlugin\\Foo` -> `plugins/myplugin/src/Foo.php`
    // deprecated in favor of `GlpiPlugin\\MyPlugin\\Foo` -> `plugins/myplugin/src/MyPlugin/Foo.php`.
    $psr4_unprefixed_path = sprintf('%s/src/%s.php', $plugin_path, str_replace('\\', '/', $plugin_class));
    if (file_exists($legacy_path)) {
        include_once($legacy_path);
    } else if (strpos($classname, NS_PLUG) !== 0 && file_exists($psr4_styled_path)) {
        include_once($psr4_styled_path);
    } else if (strpos($classname, NS_PLUG) !== 0 && file_exists($psr4_unprefixed_path)) {
        Toolbox::deprecated(
            sprintf(
                'To PSR-4 autoloading of plugins classes now expects the class `%s` to be placed in `%s`',
                $classname,
                implode(DIRECTORY_SEPARATOR, [$plugin_key, 'src', $plugin_name, str_replace('\\', DIRECTORY_SEPARATOR, $plugin_class)])
            )
        );
        include_once($psr4_unprefixed_path);
    }
}

spl_autoload_register('glpi_autoload');
