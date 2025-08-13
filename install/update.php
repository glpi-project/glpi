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
use Glpi\Toolbox\VersionParser;

/**
 * @var bool $HEADER_LOADED
 */
global $CFG_GLPI, $DB, $HEADER_LOADED;

if (($_SESSION['can_process_update'] ?? false) && isset($_POST['update_end'])) {
    if (isset($_POST['send_stats'])) {
        Telemetry::enable();
    }
    header('Location: ../index.php');
}

//test la connection a la base de donn???.
function test_connect()
{
    global $DB;

    if ($DB->errno() == 0) {
        return true;
    }
    return false;
}

/**
 * Display security key check form.
 *
 * @return void
 */
function showSecurityKeyCheckForm()
{
    global $DB;

    echo '<form action="update.php" method="post">';
    echo '<input type="hidden" name="continuer" value="1" />';
    echo '<input type="hidden" name="missing_key_warning_shown" value="1" />';
    echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
    echo '<div class="text-center">';
    echo '<h3>' . __s('Missing security key file') . '</h3>';
    echo '<div class="d-flex alert alert-warning">';
    echo '<i class="fa fa-3x fa-exclamation-triangle text-warning"></i>';
    echo '<p class="text-start">';
    echo sprintf(
        __s('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.'),
        htmlescape((new Update($DB))->getExpectedSecurityKeyFilePath())
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

echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div id='logo_bloc'></div>";
echo "<h2>" . __s('GLPI setup') . "</h2>";
echo "<br><h3>" . __s('Upgrade') . "</h3>";

if (($_SESSION['can_process_update'] ?? false) === false) {
    // Unexpected direct access to the form
    echo "<div class='center'>";
    echo "<h3><span class='migred'>" . __s('Impossible to accomplish an update by this way!') . "</span>";
    echo "<p>";
    echo "<a class='btn btn-primary' href='../index.php'>
        " . __s('Go back to GLPI') . "
     </a></p>";
    echo "</div>";
} elseif (empty($_POST["continuer"]) && empty($_POST["from_update"]) && empty($_POST["post_update_step"])) {
    // step 1    avec bouton de confirmation
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
} elseif (!empty($_POST["continuer"])) {
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
            (new Update($DB))->isExpectedSecurityKeyFileMissing()
            && (!isset($_POST['missing_key_warning_shown']) || !isset($_POST['ignore']))
        ) {
            // Display missing security key file form if key file is missing
            // unless it has already been displayed and user clicks on "ignore" button.
            showSecurityKeyCheckForm();
        } else {
            echo "<p>" . __s('Updating the database...') . "</p>";

            echo '<div id="glpi_update_messages_container"></div>';

            echo '<div class="text-center">';
            echo '<div id="glpi_update_success" class="d-none">';
            echo "<form action='update.php' method='post' class='d-inline'>";
            echo "<input type='hidden' name='post_update_step' value='1'>";
            echo "<button type='submit' name='submit' class='btn btn-primary' disabled='disabled'>";
            echo __s('Continue');
            echo "<i class='fas fa-chevron-right ms-1'></i>";
            echo "</button>";
            Html::closeForm();
            echo '</div>';
            echo '</div>';

            echo <<<HTML
                <script defer type="module">
                    import { update_database } from '/js/modules/GlpiInstall.js';
                    update_database();
                </script>
            HTML;
        }
    } else {
        echo "<h3>";
        echo __s("Connection to database failed, verify the connection parameters included in config_db.php file") . "</h3>";
    }
} elseif (!empty($_POST["post_update_step"])) {
    $_SESSION['telemetry_from_install'] = true;

    TemplateRenderer::getInstance()->display('install/post_update_step.html.twig', [
        'is_db_consistent'  => (new Update($DB))->isUpdatedSchemaConsistent(),
        'glpinetwork'       => GLPINetwork::showInstallMessage(),
        'glpinetwork_url'   => GLPI_NETWORK_SERVICES,
        'telemetry_enabled' => Telemetry::isEnabled(),
        'telemetry_info'    => Telemetry::showTelemetry(),
        'reference_info'    => Telemetry::showReference(),
    ]);
}

echo "</div></div></body></html>";
