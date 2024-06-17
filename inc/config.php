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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\System\RequirementsManager;
use Glpi\Toolbox\VersionParser;

/**
 * @var array $CFG_GLPI
 * @var \GLPI $GLPI;
 * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
 * @var bool|null $skip_db_check
 * @var bool|null $dont_check_maintenance_mode
 * @var bool|null $USEDBREPLICATE
 * @var bool|null $DBCONNECTION_REQUIRED
 */
global $CFG_GLPI,
    $GLPI,
    $GLPI_CACHE,
    $skip_db_check, $dont_check_maintenance_mode,
    $USEDBREPLICATE, $DBCONNECTION_REQUIRED
;

include_once(GLPI_ROOT . "/inc/based_config.php");

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

if ($skip_db_check ?? false) {
    $missing_db_config = false;
} elseif (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
    $missing_db_config = true;
} else {
    include_once(GLPI_CONFIG_DIR . "/config_db.php");
    $missing_db_config = !class_exists('DB', false);
}

if ($missing_db_config) {
    Session::loadLanguage('', false);

    if (!isCommandLine()) {
        // Prevent inclusion of debug informations in footer, as they are based on vars that are not initialized here.
        $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;

        Html::nullHeader('Missing configuration', $CFG_GLPI["root_doc"]);
        $twig_params = [
            'config_db' => GLPI_CONFIG_DIR . '/config_db.php',
            'install_exists' => file_exists(GLPI_ROOT . '/install/install.php'),
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <div class="container-fluid mb-4">
                <div class="row justify-content-center">
                    <div class="col-xl-6 col-lg-7 col-md-9 col-sm-12">
                        <h2>GLPI seems to not be configured properly.</h2>
                        <p class="mt-2 mb-n2 alert alert-warning">
                            Database configuration file "{{ config_db }}" is missing or is corrupted.
                            You have to either restart the install process, either restore this file.
                            <br />
                            <br />
                            {% if install_exists %}
                                <a class="btn btn-primary" href="{{ path('install/install.php') }}">Go to install page</a>
                            {% endif %}
                        </p>
                    </div>
                </div>
            </div>
TWIG, $twig_params);
        Html::nullFooter();
    } else {
        echo "GLPI seems to not be configured properly.\n";
        echo sprintf('Database configuration file "%s" is missing or is corrupted.', GLPI_CONFIG_DIR . '/config_db.php') . "\n";
        echo "You have to either restart the install process, either restore this file.\n";
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
        // When `$_SESSION["glpiroot"]` differs from `$CFG_GLPI["root_doc"]`, it means that
        // either web server configuration changed,
        // either session was initialized on another GLPI instance.
        // Destroy session and redirect to login to ensure that session from another GLPI instance is not reused.
        Session::destroy();
        Auth::setRememberMeCookie('');
        Html::redirectToLogin();
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
        Session::checkCookieSecureConfig();

        // Prevent debug bar to be displayed when an admin user was connected with debug mode when codebase was updated.
        Toolbox::setDebugMode(Session::NORMAL_MODE);

        Session::loadLanguage('', false);

        if (isCommandLine()) {
            echo __('The GLPI codebase has been updated. The update of the GLPI database is necessary.');
            echo "\n";
            exit();
        }

        if (!defined('SKIP_UPDATES')) {
            /** @var \DBmysql $DB */
            global $DB;

            $twig_params = [
                'core_requirements' => (new RequirementsManager())->getCoreRequirementList($DB),
                'try_again'         => __('Try again'),
                'update_needed'     => __('The GLPI codebase has been updated. The update of the GLPI database is necessary.'),
                'upgrade'           => _sx('button', 'Upgrade'),
                'outdated_files'    => __('You are trying to use GLPI with outdated files compared to the version of the database. Please install the correct GLPI files corresponding to the version of your database.'),
                'stable_release'    => VersionParser::isStableRelease(GLPI_VERSION),
                'agree_unstable'    => Config::agreeUnstableMessage(VersionParser::isDevVersion(GLPI_VERSION)),
                'outdated'          => version_compare(
                    VersionParser::getNormalizedVersion($CFG_GLPI['version'] ?? '0.0.0-dev'),
                    VersionParser::getNormalizedVersion(GLPI_VERSION),
                    '>'
                )
            ];

            Html::nullHeader(__('Update needed'), $CFG_GLPI["root_doc"]);
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div class="container-fluid mb-4">
                    <div class="row justify-content-evenly">
                        <div class="col-12 col-xxl-6">
                            <div class="card text-center mb-4">
                                {% include 'install/blocks/requirements_table.html.twig' with {'requirements': core_requirements} %}
                                {% if core_requirements.hasMissingMandatoryRequirements() or core_requirements.hasMissingOptionalRequirements() %}
                                    <form action="{{ path('index.php') }}" method="post">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-redo"></i>{{ try_again }}
                                        </button>
                                    </form>
                                {% endif %}
                                {% if not core_requirements.hasMissingMandatoryRequirements() %}
                                    {% if not outdated %}
                                        <form method="post" action="{{ path('install/update.php') }}">
                                            {% if not stable_release %}
                                                {{ agree_unstable|raw }}
                                            {% endif %}
                                            <p class="mt-2 mb-n2 alert alert-important alert-warning">
                                                {{ update_needed }}
                                            </p>
                                            <button type="submit" name="from_update" class="btn btn-primary">
                                                <i class="fas fa-check"></i>{{ upgrade }}
                                            </button>
                                        </form>
                                    {% else %}
                                        <p class="mt-2 mb-n2 alert alert-important alert-warning">
                                            {{ outdated_files }}
                                        </p>
                                    {% endif %}
                                {% endif %}
                            </div>
                        </div>
                    </div>
                </div>
TWIG, $twig_params);
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
