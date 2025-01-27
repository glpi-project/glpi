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

/**
 * @var \DBmysql|null $DB
 * @var array $CFG_GLPI
 */
global $DB, $CFG_GLPI;

if (PHP_SAPI === 'cli') {
    // Check the resources state before trying to instanciate the Kernel.
    // It must be done here as this check must be done even when the Kernel
    // cannot be instanciated due to missing dependencies.
    require_once dirname(__DIR__) . '/src/Glpi/Application/ResourcesChecker.php';
    (new \Glpi\Application\ResourcesChecker(dirname(__DIR__)))->checkResources();

    require_once dirname(__DIR__) . '/vendor/autoload.php';

    $kernel = new \Glpi\Kernel\Kernel();
    $kernel->boot();

    // Handle the `--debug` argument
    $debug = array_search('--debug', $_SERVER['argv']);
    if ($debug) {
        $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
        unset($_SERVER['argv'][$debug]);
        $_SERVER['argv'] = array_values($_SERVER['argv']);
        $_SERVER['argc']--;
    }

    if ($CFG_GLPI['maintenance_mode'] ?? false) {
        echo 'Service is down for maintenance. It will be back shortly.' . PHP_EOL;
        exit();
    }

    if (!($DB instanceof DBmysql)) {
        echo sprintf(
            'ERROR: The database configuration file "%s" is missing or is corrupted. You have to either restart the install process, or restore this file.',
            GLPI_CONFIG_DIR . '/config_db.php'
        ) . PHP_EOL;
        exit();
    }

    if (!$DB->connected) {
        echo 'ERROR: The connection to the SQL server could not be established. Please check your configuration.' . PHP_EOL;
        exit();
    }

    if (!Config::isLegacyConfigurationLoaded()) {
        echo 'ERROR: Unable to load the GLPI configuration from the database.' . PHP_EOL;
        exit();
    }

    if (!defined('SKIP_UPDATES') && !Update::isDbUpToDate()) {
        echo 'The GLPI codebase has been updated. The update of the GLPI database is necessary.' . PHP_EOL;
        exit();
    }
}

// Ensure current directory when run from crontab
chdir(__DIR__);

// Try detecting if we are running with the root user (Not available on Windows)
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    echo "\t" . 'WARNING: running as root is discouraged.' . "\n";
    echo "\t" . 'You should run the script as the same user that your web server runs as to avoid file permissions being ruined.' . "\n";
    if (!in_array('--allow-superuser', $_SERVER['argv'], true)) {
        echo "\t" . 'Use --allow-superuser option to bypass this limitation.' . "\n";
        exit(1);
    }
}

if (!is_writable(GLPI_LOCK_DIR)) {
    echo "\t" . sprintf('ERROR: %s is not writable.' . "\n", GLPI_LOCK_DIR);
    echo "\t" . 'Run the script as the same user that your web server runs as.' . "\n";
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
} else if (isset($_SERVER['argc']) && ($_SERVER['argc'] > 1)) {
   // Parse command line options

    $mode = CronTask::MODE_EXTERNAL; // when taskname given, will allow --force
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        if ($_SERVER['argv'][$i] == '--force') {
            $mode = -CronTask::MODE_EXTERNAL;
        } else if (is_numeric($_SERVER['argv'][$i])) {
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
