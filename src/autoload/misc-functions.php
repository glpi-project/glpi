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

use Twig\Runtime\EscaperRuntime;

use function Safe\preg_match;

/**
 * Is the script launch in Command line?
 *
 * @return boolean
 */
function isCommandLine()
{
    return (PHP_SAPI == 'cli');
}

/**
 * Is the script launched From API?
 *
 * @return boolean
 */
function isAPI()
{
    $script = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($script, 'api.php')) {
        return true;
    }
    if (str_contains($script, 'apirest.php')) {
        return true;
    }

    return false;
}

/**
 * Determine if an class name is a plugin one
 *
 * @param string $classname Class name to analyze
 *
 * @return boolean|array False or an array containing plugin name and class name
 */
function isPluginItemType($classname)
{
    $matches = [];
    if (preg_match("/^Plugin([A-Z][a-z0-9]+)([A-Z]\w+)$/", $classname, $matches)) {
        $plug           = [];
        $plug['plugin'] = $matches[1];
        $plug['class']  = $matches[2];
        return $plug;
    } elseif (str_starts_with($classname, NS_PLUG)) {
        $tab = explode('\\', $classname, 3);
        $plug           = [];
        $plug['plugin'] = $tab[1];
        $plug['class']  = $tab[2];
        return $plug;
    }
    // Standard case
    return false;
}

/**
 * Escape a string to make it safe to be printed in an HTML page.
 * This function is pretty similar to the `htmlspecialchars` function, but its signature is less strict.
 *
 * This function will be deprecated/removed once all the HTML code of GLPI will be moved inside Twig templates.
 *
 * @param mixed $str
 * @return string
 */
function htmlescape(mixed $str): string
{
    return htmlspecialchars((string) $str);
}

/**
 * Escape a string to make it safe to be printed in a JS string variable.
 *
 * This function will be deprecated/removed once all the JS code of GLPI will be moved inside JS files or Twig templates.
 *
 * @param mixed $str
 * @return string
 */
function jsescape(mixed $str): string
{
    // Rely on the Twig escaper
    return (new EscaperRuntime())->escape((string) $str, 'js');
}
