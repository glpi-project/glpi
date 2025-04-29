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

/** @var array $CFG_GLPI */
global $CFG_GLPI;

// Ensure current directory when run from crontab
chdir(__DIR__);

$SECURITY_STRATEGY = 'no_check'; // in GLPI mode, cronjob can also be triggered from public pages

include('../inc/includes.php');

if (!is_writable(GLPI_LOCK_DIR)) {
    //TRANS: %s is a directory
    echo "\t" . sprintf(__('ERROR: %s is not writable') . "\n", GLPI_LOCK_DIR);
    echo "\t" . __('run script as apache user') . "\n";
    exit(1);
}

if (!isCommandLine()) {
    //The advantage of using background-image is that cron is called in a separate
    //request and thus does not slow down output of the main page as it would if called
    //from there.
    $image = pack("H*", "47494638396118001800800000ffffff00000021f90401000000002c0000000" .
                       "018001800000216848fa9cbed0fa39cb4da8bb3debcfb0f86e248965301003b");
    header("Content-Type: image/gif");
    header("Content-Length: " . strlen($image));
    header("Cache-Control: no-cache,no-store");
    header("Pragma: no-cache");
    header("Connection: close");
    echo $image;
    flush();

    CronTask::launch(CronTask::MODE_INTERNAL);
} elseif (isset($_SERVER['argc']) && ($_SERVER['argc'] > 1)) {
    // Parse command line options

    $mode = CronTask::MODE_EXTERNAL; // when taskname given, will allow --force
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        if ($_SERVER['argv'][$i] == '--force') {
            $mode = -CronTask::MODE_EXTERNAL;
        } elseif (is_numeric($_SERVER['argv'][$i])) {
            // Number of tasks
            CronTask::launch(CronTask::MODE_EXTERNAL, intval($_SERVER['argv'][$i]));
            // Only check first parameter when numeric is passed
            break;
        } else {
            // Single Task name
            CronTask::launch($mode, 1, $_SERVER['argv'][$i]);
        }
    }
} else {
    // Default from configuration
    CronTask::launch(CronTask::MODE_EXTERNAL, $CFG_GLPI['cron_limit']);
}
