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

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

require dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new \Glpi\Kernel\Kernel();
$kernel->boot();

if (isset($_SERVER['argc'])) {
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        $it           = explode("=", $_SERVER['argv'][$i], 2);
        $it[0]        = preg_replace('/^--/', '', $it[0]);
        $_GET[$it[0]] = ($it[1] ?? true);
    }
}
$NEEDED_ITEMS = ["mailgate", "mailing"];

if (isset($_GET['from'])) {
    $from = $_GET['from'];
} else {
    $from = $CFG_GLPI['admin_email'];
}

if (isset($_GET['to'])) {
    $dest = $_GET['to'];
} else {
    die("--to option is mandatory\n");
}

if (isset($_GET['enc'])) {
    $enc = $_GET['enc'];
} else {
    // "7bit", "binary", "base64", and "quoted-printable".
    $enc = '';
}

if (isset($_GET['help'])) {
    die("usage php testmail.php  [ --from=email ] --to=email [ --enc=7bit|8bit|binary|base64|quoted-printable ]\n");
}

$dat     = date('r');
$secret  = "l'été, ça roule !";

echo "From : $from\n";
echo "To : $dest\n";
echo "Date : $dat\n";

$mmail = new NotificationMailing();
$mmail->From = $from;
$mmail->FromName = "GLPI test";
$mmail->isHTML(true);

if ($enc) {
    $mmail->Encoding = $enc;
}

$mmail->Subject = "GLPI test mail" . ($enc ? " ($enc)" : '');
$mmail->Body = "<html><body><h3>GLPI test mail</h3><p>Encoding = <span class='b'>$enc</span></p>" .
             "<p>Date = <span class='b'>$dat</span></p><p>Secret = <span class='b'>$secret</span>" .
             "</p></body></html>";
$mmail->AltBody = "GLPI test mail\nEncoding : $enc\nDate : $dat\nSecret=$secret";

$mmail->AddAddress($dest, "");

$logo = file_get_contents("../pics/logos/logo-GLPI-100-black.png");
$mmail->AddStringAttachment($logo, 'glpi.png', ($enc ? $enc : 'base64'), 'image/png');

$mmail->AddStringAttachment($secret, 'secret.txt', ($enc ? $enc : 'base64'), 'text/plain');

echo "Send : " . ($mmail->Send() ? "OK\n" : "Failed (" . $mmail->ErrorInfo . ")\n");
