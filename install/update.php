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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\Toolbox\VersionParser;

/**
 * @var \DBmysql|null $DB
 * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
 * @var \Update $update
 * @var bool $HEADER_LOADED
 */
global $DB,
       $GLPI_CACHE,
       $update,
       $HEADER_LOADED;

$GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance();

$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

$update = new Update($DB);

if (isset($_POST['update_end'])) {
    if (isset($_POST['send_stats'])) {
        Telemetry::enable();
    }
    header('Location: ../index.php');
}


//update database
function doUpdateDb()
{
    /**
     * @var \Migration $migration
     * @var \Update $update
     */
    global $migration, $update;

    $currents            = $update->getCurrents();
    $current_version     = $currents['version'];
    $current_db_version  = $currents['dbversion'];

    $migration = new Migration(GLPI_VERSION);
    $update->setMigration($migration);

    if (
        !VersionParser::isStableRelease(GLPI_VERSION)
        && $current_db_version != GLPI_SCHEMA_VERSION && !isset($_POST['agree_unstable'])
    ) {
        return;
    }

    $update->doUpdates($current_version);

    // Force cache cleaning to ensure it will not contain stale data
    (new CacheManager())->resetAllCaches();

    if (!$update->isUpdatedSchemaConsistent()) {
        $migration->displayError(
            __('The database schema is not consistent with the current GLPI version.')
            . "\n"
            . sprintf(
                __('It is recommended to run the "%s" command to see the differences.'),
                'php bin/console database:check_schema_integrity'
            )
        );
    }
}

/**
 * Display security key check form.
 *
 * @return void
 */
function showSecurityKeyCheckForm()
{
    /**
     * @var \Update $update
     */
    global $update;

    echo '<form action="update.php" method="post">';
    echo '<input type="hidden" name="continuer" value="1" />';
    echo '<input type="hidden" name="missing_key_warning_shown" value="1" />';
    echo '<div class="text-center">';
    echo '<h3>' . __s('Missing security key file') . '</h3>';
    echo '<div class="d-flex alert alert-warning">';
    echo '<i class="fa fa-3x fa-exclamation-triangle text-warning"></i>';
    echo '<p class="text-start">';
    echo sprintf(
        __s('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.'),
        htmlescape($update->getExpectedSecurityKeyFilePath())
    );
    echo '</p>';
    echo '</div>';
    echo '<input type="submit" name="ignore" class="btn btn-primary" value="' . __s('Ignore warning') . '" />';
    echo '&nbsp;&nbsp;';
    echo '<input type="submit" name="retry" class="btn btn-primary" value="' . __s('Try again') . '" />';
    echo '</form>';
}

//Debut du script
$HEADER_LOADED = true;

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

TemplateRenderer::getInstance()->display('layout/parts/head.html.twig', [
    'lang'  => $_SESSION['glpilanguage'],
    'title' => __('GLPI setup'),
    'css_files' => [
        ['path' => 'lib/tabler.css'],
        ['path' => 'lib/base.css'],
        ['path' => 'css/install.scss'],
    ],
    'js_files' => [
        ['path' => 'lib/base.js'],
        ['path' => 'lib/fuzzy.js'],
        ['path' => 'js/common.js'],
        ['path' => 'js/glpi_dialog.js'],
    ],
    'js_modules' => [],
    'custom_header_tags' => [],
]);

// CFG
echo Html::getCoreVariablesForJavascript();

echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div id='logo_bloc'></div>";
echo "<h2>" . __s('GLPI setup') . "</h2>";
echo "<br><h3>" . __s('Upgrade') . "</h3>";

// step 1    avec bouton de confirmation

if (empty($_POST["continuer"]) && empty($_POST["from_update"])) {
    if (empty($from_install) && !isset($_POST["from_update"])) {
        echo "<div class='center'>";
        echo "<h3><span class='migred'>" . __s('Impossible to accomplish an update by this way!') . "</span>";
        echo "<p>";
        echo "<a class='btn btn-primary' href='../index.php'>
            " . __s('Go back to GLPI') . "
         </a></p>";
        echo "</div>";
    } else {
        echo "<div class='center'>";
        echo "<h3 class='my-4'><span class='migred p-2'>" . sprintf(__s('Caution! You will update the GLPI database named: %s'), htmlescape($DB->dbdefault)) . "</h3>";

        echo "<form action='update.php' method='post'>";
        if (!VersionParser::isStableRelease(GLPI_VERSION)) {
            echo Config::agreeUnstableMessage(VersionParser::isDevVersion(GLPI_VERSION));
        }
        echo "<button type='submit' class='btn btn-primary' name='continuer' value='1'>
         " . __s('Continue') . "
         <i class='fas fa-chevron-right ms-1'></i>
      </button>";
        Html::closeForm();
        echo "</div>";
    }
} else {
   // Step 2
    if ($DB->connected) {
        echo "<h3>" . __s('Database connection successful') . "</h3>";
        echo "<p class='text-center'>";
        $result = Config::displayCheckDbEngine(true);
        echo "</p>";
        if ($result > 0) {
            return;
        }
        if (
            $update->isExpectedSecurityKeyFileMissing()
            && (!isset($_POST['missing_key_warning_shown']) || !isset($_POST['ignore']))
        ) {
           // Display missing security key file form if key file is missing
           // unless it has already been displayed and user clicks on "ignore" button.
            showSecurityKeyCheckForm();
        } else {
            echo "<div class='text-center'>";
            doUpdateDb();
            echo "</div>";

            Session::destroy(); // Remove session data set by web installation

            $_SESSION['telemetry_from_install'] = true;

            TemplateRenderer::getInstance()->display('install/update.html.twig', [
                'glpinetwork'       => GLPINetwork::showInstallMessage(),
                'glpinetwork_url'   => GLPI_NETWORK_SERVICES,
                'telemetry_enabled' => Telemetry::isEnabled(),
                'telemetry_info'    => Telemetry::showTelemetry(),
                'reference_info'    => Telemetry::showReference(),
            ]);
        }
    } else {
        echo "<h3>";
        echo __s("Connection to database failed, verify the connection parameters included in config_db.php file") . "</h3>";
    }
}

echo "</div></div></body></html>";
