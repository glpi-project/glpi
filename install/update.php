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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Cache\CacheManager;
use Glpi\Toolbox\VersionParser;

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', realpath('..'));
}

include_once(GLPI_ROOT . "/inc/based_config.php");
include_once(GLPI_ROOT . "/inc/db.function.php");
include_once(GLPI_CONFIG_DIR . "/config_db.php");

/**
 * @var \DBmysql $DB
 * @var \GLPI $GLPI
 * @var \Psr\SimpleCache\CacheInterface $GLPI_CACHE
 */
global $DB, $GLPI, $GLPI_CACHE;

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

$GLPI_CACHE = (new CacheManager())->getInstallerCacheInstance();

Config::detectRootDoc();

if (!($DB instanceof DBmysql)) { // $DB can have already been init in install.php script
    $DB = new DB();
}
$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

Config::loadLegacyConfiguration();

$update = new Update($DB);
$update->initSession();

if (isset($_POST['update_end'])) {
    if (isset($_POST['send_stats'])) {
        Telemetry::enable();
    }
    header('Location: ../index.php');
}

/* ----------------------------------------------------------------- */

/*---------------------------------------------------------------------*/
/**
 * To be conserved to migrations before 0.80
 * since 0.80, migration is a new class
 **/
function displayMigrationMessage($id, $msg = "")
{
    static $created = 0;
    static $deb;

    if ($created != $id) {
        if (empty($msg)) {
            $msg = __('Work in progress...');
        }
        echo "<div id='migration_message_$id'><p class='center'>$msg</p></div>";
        $created = $id;
        $deb     = time();
    } else {
        if (empty($msg)) {
            $msg = __('Task completed.');
        }
        $fin = time();
        $tps = Html::timestampToString($fin - $deb);
        echo "<script type='text/javascript'>document.getElementById('migration_message_$id').innerHTML =
             '<p class=\"center\" >$msg ($tps)</p>';</script>\n";
    }
    Html::glpi_flush();
}


//test la connection a la base de donn???.
function test_connect()
{
    /** @var \DBmysql $DB */
    global $DB;

    if ($DB->error == 0) {
        return true;
    }
    return false;
}



//update database
function doUpdateDb()
{
    /**
     * @var \Migration $migration
     * @var \Update $update
     */
    global $migration, $update;

    // Init debug variable
    // Only show errors
    Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

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
    echo '<h3>' . __('Missing security key file') . '</h3>';
    echo '<div class="d-flex alert alert-warning">';
    echo '<i class="fa fa-3x fa-exclamation-triangle text-warning"></i>';
    echo '<p class="text-start">';
    echo sprintf(
        __('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.'),
        $update->getExpectedSecurityKeyFilePath()
    );
    echo '</p>';
    echo '</div>';
    echo '<input type="submit" name="ignore" class="btn btn-primary" value="' . __('Ignore warning') . '" />';
    echo '&nbsp;&nbsp;';
    echo '<input type="submit" name="retry" class="btn btn-primary" value="' . __('Try again') . '" />';
    echo '</form>';
}

//Debut du script
$HEADER_LOADED = true;

Session::start();

Session::loadLanguage('', false);

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<title>Setup GLPI</title>";
//JS
echo Html::script("public/lib/base.js");
echo Html::script("js/glpi_dialog.js");
// CSS
echo Html::css('public/lib/base.css');
echo Html::scss("css/install", [], true);
echo "</head>";
echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div id='logo_bloc'></div>";
echo "<h2>GLPI SETUP</h2>";
echo "<br><h3>" . __('Upgrade') . "</h3>";

// step 1    avec bouton de confirmation

if (empty($_POST["continuer"]) && empty($_POST["from_update"])) {
    if (empty($from_install) && !isset($_POST["from_update"])) {
        echo "<div class='center'>";
        echo "<h3><span class='migred'>" . __('Impossible to accomplish an update by this way!') . "</span>";
        echo "<p>";
        echo "<a class='btn btn-primary' href='../index.php'>
            " . __('Go back to GLPI') . "
         </a></p>";
        echo "</div>";
    } else {
        echo "<div class='center'>";
        echo "<h3 class='my-4'><span class='migred p-2'>" . sprintf(__('Caution! You will update the GLPI database named: %s'), $DB->dbdefault) . "</h3>";

        echo "<form action='update.php' method='post'>";
        if (!VersionParser::isStableRelease(GLPI_VERSION)) {
            echo Config::agreeUnstableMessage(VersionParser::isDevVersion(GLPI_VERSION));
        }
        echo "<button type='submit' class='btn btn-primary' name='continuer' value='1'>
         " . __('Continue') . "
         <i class='fas fa-chevron-right ms-1'></i>
      </button>";
        Html::closeForm();
        echo "</div>";
    }
} else {
   // Step 2
    if (test_connect()) {
        echo "<h3>" . __('Database connection successful') . "</h3>";
        echo "<p class='text-center'>";
        $result = Config::displayCheckDbEngine(true);
        echo "</p>";
        if ($result > 0) {
            die(1);
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
        echo __("Connection to database failed, verify the connection parameters included in config_db.php file") . "</h3>";
    }
}

echo "</div></div></body></html>";
