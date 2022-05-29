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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;

// Be sure to use global objects if this file is included outside normal process
global $CFG_GLPI, $GLPI, $GLPI_CACHE;

include_once(GLPI_ROOT . "/inc/based_config.php");

Session::setPath();
Session::start();

// Default Use mode
if (!isset($_SESSION['glpi_use_mode'])) {
    $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
}

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

//init cache
$cache_manager = new CacheManager();
$GLPI_CACHE = $cache_manager->getCoreCacheInstance();

Config::detectRootDoc();

if (!isset($skip_db_check) && !file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
    Session::loadLanguage('', false);

    // no translation
    $title_text        = 'GLPI seems to not be configured properly.';
    $missing_conf_text = sprintf('Database configuration file "%s" is missing.', GLPI_CONFIG_DIR . '/config_db.php');
    $hint_text         = 'You have to either restart the install process, either restore this file.';

    if (!isCommandLine()) {
        // Prevent inclusion of debug informations in footer, as they are based on vars that are not initialized here.
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        Html::nullHeader('Missing configuration', $CFG_GLPI["root_doc"]);
        echo '<div class="container-fluid mb-4">';
        echo '<div class="row justify-content-center">';
        echo '<div class="col-xl-6 col-lg-7 col-md-9 col-sm-12">';
        echo '<h2>' . $title_text . '</h2>';
        echo '<p class="mt-2 mb-n2 alert alert-warning">';
        echo $missing_conf_text;
        echo ' ';
        echo $hint_text;
        echo '<br />';
        echo '<br />';
        if (file_exists(GLPI_ROOT . '/install/install.php')) {
            echo '<a class="btn btn-primary" href="' . $CFG_GLPI['root_doc'] . '/install/install.php">Go to install page</a>';
        }
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        Html::nullFooter();
    } else {
        echo $title_text . "\n";
        echo $missing_conf_text . "\n";
        echo $hint_text . "\n";
    }
    die(1);
} else {
   // *************************** Statics config options **********************
   // ********************options d'installation statiques*********************
   // *************************************************************************

    if (!isset($skip_db_check)) {
        include_once(GLPI_CONFIG_DIR . "/config_db.php");

       //Database connection
        DBConnection::establishDBConnection(
            (isset($USEDBREPLICATE) ? $USEDBREPLICATE : 0),
            (isset($DBCONNECTION_REQUIRED) ? $DBCONNECTION_REQUIRED : 0)
        );

       //Options from DB, do not touch this part.
        if (!Config::loadLegacyConfiguration()) {
            echo "Error accessing config table";
            exit();
        }
    }

    if (
        isCommandLine()
        && !defined('TU_USER') // In test suite context, used --debug option is the atoum one
        && isset($_SERVER['argv'])
    ) {
        $key = array_search('--debug', $_SERVER['argv']);
        if ($key) {
            $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
            unset($_SERVER['argv'][$key]);
            $_SERVER['argv']           = array_values($_SERVER['argv']);
            $_SERVER['argc']--;
        }
    }
    Toolbox::setDebugMode();

    if (isset($_SESSION["glpiroot"]) && $CFG_GLPI["root_doc"] != $_SESSION["glpiroot"]) {
        Html::redirect($_SESSION["glpiroot"]);
    }

    if (!isset($_SESSION["glpilanguage"])) {
        $_SESSION["glpilanguage"] = Session::getPreferredLanguage();
    }

   // Override cfg_features by session value
    foreach ($CFG_GLPI['user_pref_field'] as $field) {
        if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
            $_SESSION["glpi$field"] = $CFG_GLPI[$field];
        }
    }

   // Check maintenance mode
    if (
        isset($CFG_GLPI["maintenance_mode"])
        && $CFG_GLPI["maintenance_mode"]
        && !isset($dont_check_maintenance_mode)
    ) {
        if (isset($_GET['skipMaintenance']) && $_GET['skipMaintenance']) {
            $_SESSION["glpiskipMaintenance"] = 1;
        }

        if (!isset($_SESSION["glpiskipMaintenance"]) || !$_SESSION["glpiskipMaintenance"]) {
            Session::loadLanguage('', false);
            if (isCommandLine()) {
                echo __('Service is down for maintenance. It will be back shortly.');
                echo "\n";
            } else {
                Glpi\Application\View\TemplateRenderer::getInstance()->display('maintenance.html.twig', [
                    'title'            => "MAINTENANCE MODE",
                    'maintenance_text' => $CFG_GLPI["maintenance_text"] ?? "",
                ]);
            }
            exit();
        }
    }
    // Check version
    if (!isset($_GET["donotcheckversion"]) && !Update::isDbUpToDate()) {
        Session::loadLanguage('', false);

        if (isCommandLine()) {
            echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.');
            echo "\n";
            exit();
        }

        if (defined('SKIP_UPDATES')) {
            // Show warning for the main request
            if (!Toolbox::isAjax()) {
                echo "<div class='banner-need-update'>";
                echo __("You are bypassing a needed update");
                echo "</div>";
            }
        } else {
            Html::nullHeader(__('Update needed'), $CFG_GLPI["root_doc"]);
            echo "<div class='container-fluid mb-4'>";
            echo "<div class='row justify-content-evenly'>";
            echo "<div class='col-12 col-xxl-6'>";
            echo "<div class='card text-center mb-4'>";

            global $DB;
            $core_requirements = (new RequirementsManager())->getCoreRequirementList($DB);
            TemplateRenderer::getInstance()->display(
                'install/blocks/requirements_table.html.twig',
                [
                    'requirements' => $core_requirements,
                ]
            );

            if ($core_requirements->hasMissingMandatoryRequirements() || $core_requirements->hasMissingOptionalRequirements()) {
                echo "<form action='" . $CFG_GLPI["root_doc"] . "/index.php' method='post'>";
                echo Html::submit(__s('Try again'), [
                    'class' => "btn btn-primary",
                    'icon'  => "fas fa-redo",
                ]);
                Html::closeForm();
            }
            if (!$core_requirements->hasMissingMandatoryRequirements()) {
                $outdated = version_compare(
                    VersionParser::getNormalizedVersion($CFG_GLPI['version'] ?? '0.0.0-dev'),
                    VersionParser::getNormalizedVersion(GLPI_VERSION),
                    '>'
                );

                if ($outdated !== true) {
                    echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/install/update.php'>";
                    if (!VersionParser::isStableRelease(GLPI_VERSION)) {
                        echo Config::agreeUnstableMessage(VersionParser::isDevVersion(GLPI_VERSION));
                    }
                    echo "<p class='mt-2 mb-n2 alert alert-important alert-warning'>";
                    echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.') . "</p>";
                    echo Html::submit(_sx('button', 'Upgrade'), [
                        'name'  => 'from_update',
                        'class' => "btn btn-primary",
                        'icon'  => "fas fa-check",
                    ]);
                    Html::closeForm();
                } else {
                    echo "<p class='mt-2 mb-n2 alert alert-important alert-warning'>" .
                        __('You are trying to use GLPI with outdated files compared to the version of the database. Please install the correct GLPI files corresponding to the version of your database.') . "</p>";
                }
            }

            echo "</div>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
            Html::nullFooter();
            exit();
        }
    }
}

// First call to `Config::detectRootDoc()` cannot compute the value
// in CLI context, as it requires DB connection to be up.
// Now DB is up, so value can be computed.
if (!isset($CFG_GLPI['root_doc'])) {
    Config::detectRootDoc();
}
