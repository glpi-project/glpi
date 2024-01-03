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

define('GLPI_ROOT', dirname(__DIR__, 1));

if (!chdir(GLPI_ROOT)) {
    echo "\033[01;31m" . sprintf('Unable to change directory to "%s".', GLPI_ROOT) . "\033[0m" . PHP_EOL;
    exit(1);
}

// Define autoloaders
include 'vendor/autoload.php';
spl_autoload_register(
    function ($class) {
        include 'inc/' . implode('/', explode('\\', preg_replace('/^glpi\\\\/', '', strtolower($class)))) . '.class.php';
    }
);

// Include all files to be able get declared classes/traits/interfaces
$dir_iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(GLPI_ROOT . '/inc'),
    RecursiveIteratorIterator::SELF_FIRST
);
/** @var SplFileInfo $file */
foreach ($dir_iterator as $file) {
    if (preg_match('/\.class\.php$/', $file->getFilename()) !== 1) {
        continue;
    }
    require_once $file->getRealPath();
}

$classes = array_merge(get_declared_classes(), get_declared_interfaces(), get_declared_traits());
foreach ($classes as $class) {
    $oldPath = 'inc/' . implode('/', explode('\\', preg_replace('/^glpi\\\\/', '', strtolower($class)))) . '.class.php';
    if (!file_exists($oldPath)) {
        continue;
    }
    $newPath = 'src/' . implode('/', explode('\\', preg_replace('/^Glpi\\\\/', '', $class))) . '.php';
    $directory = dirname($newPath);

    if (!file_exists($directory) && mkdir($directory, 0777, true) === false) {
        echo "\033[01;31m" . sprintf('Unable to create directory "%s".', $directory) . "\033[0m" . PHP_EOL;
        exit(1);
    }

    $output = null;
    $result = null;
    exec(sprintf('git mv %s %s', $oldPath, $newPath), $output, $result);
    if ($result !== 0) {
        echo "\033[01;31m" . sprintf('Error during file renaming:%s', implode(PHP_EOL, ['', ...$output])) . "\033[0m" . PHP_EOL;
        exit(1);
    }
}
