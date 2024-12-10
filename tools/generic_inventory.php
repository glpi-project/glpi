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

use Glpi\Inventory\Conf;
use Glpi\Kernel\Kernel;

require dirname(__DIR__) . '/vendor/autoload.php';

if (!isCommandLine()) {
    die('This script is only available from the command line');
}

if ($argc !== 2) {
    die("Usage: php generic_inventory.php /path/to/inventory/files>\n");
}

$kernel = new Kernel('testing');
$kernel->loadCommonGlobalConfig();

\Session::start();

$conf = new Conf();
$conf->saveConf([
    'enabled_inventory' => 1
]);

$computer = new \Computer();
$computers = $computer->find();
foreach ($computers as $computer_row) {
    $computer->delete(['id' => $computer_row['id'], true, false]);
}

//cleanup previous inventory
$definition = new \Glpi\Asset\AssetDefinition();
$definition->getFromDBByCrit(['system_name' => 'Server']);
$definition->delete(['id' => $definition->getID()], true, false);

$doInv = function ($itemtype) use ($argv)
{
    $execution_times = [];
    $memory_used = [];

    $converter = new \Glpi\Inventory\Converter();

    $inv_files_dir = realpath($argv[1]);
    $inv_files_iterator = new RegexIterator(
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($inv_files_dir, FilesystemIterator::UNIX_PATHS)
        ),
        '/\.xml$/i'
    );

    printf(
        'Processing inventory files as %s in %s' . PHP_EOL,
        $itemtype,
        $inv_files_dir
    );

    /* @var \SplFileInfo $tpl_file */
    foreach ($inv_files_iterator as $inv_file) {
        //echo sprintf("Doing inventory for %s...\n", $inv_file->getPathname());
        $contents = file_get_contents($inv_file->getPathname());
        $json_contents = json_decode($converter->convert($contents));
        $json_contents->itemtype = $itemtype;

        memory_reset_peak_usage();
        $start_time = microtime(true);

        $inventory = new \Glpi\Inventory\Inventory($json_contents);

        if ($inventory->inError()) {
            var_dump($inventory->getErrors());
        }

        $execution_time = microtime(true) - $start_time;
        $execution_times[] = $execution_time;
        $memory_used[] = memory_get_peak_usage();
        //display current execution time
        //printf("> time: %.4f\n", $execution_time);
    }

    printf("average execution time: %.4f\n", array_sum($execution_times) / count($execution_times));
    printf("average memory used (Mb): %.4f\n", (array_sum($memory_used) / count($memory_used)) / 1024 / 1024);
};

//first, do inventory from standard Computer itemtype
$doInv(\Computer::class);

//then, create a generic asset type and do the inventory again

// Initialize with all standard rights for super admin profile
$superadmin_p_id = getItemByTypeName(Profile::class, 'Super-Admin', true);
$profiles = [
    $superadmin_p_id => ALLSTANDARDRIGHT,
];

$definition->add([
    'system_name' => 'Server',
    'is_active'   => true,
    'capacities'  => array_keys(\Glpi\Asset\AssetDefinitionManager::getInstance()->getAvailableCapacities()),
    'profiles'    => $profiles,
]);
$doInv($definition->getAssetClassName());