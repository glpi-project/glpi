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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Application\Environment;
use Glpi\Error\ErrorHandler;
use Laminas\I18n\Translator\TextDomain;
use Laminas\I18n\Translator\Translator;

use function Safe\fopen;
use function Safe\json_encode;
use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\session_write_close;

global $CFG_GLPI, $TRANSLATE;

session_write_close(); // Unlocks session to permit concurrent calls

header("Content-Type: application/json; charset=UTF-8");

$is_cacheable = Environment::get()->shouldForceExtraBrowserCache();
if ($is_cacheable) {
    // Makes CSS cacheable by browsers and proxies.
    $max_age = WEEK_TIMESTAMP;
    header_remove('Pragma');
    header('Cache-Control: public');
    header('Cache-Control: max-age=' . $max_age);
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
}


// Default response to send if locales cannot be loaded.
// Prevent JS error for plugins that does not provide any translation files
$default_response = json_encode(
    [
        '' => [
            'language'     => $CFG_GLPI['languages'][$_SESSION['glpilanguage']][1],
            'plural-forms' => 'nplurals=2; plural=(n != 1);',
        ],
    ]
);

// Get messages from translator component
$messages = null;
try {
    $messages = $TRANSLATE->getAllMessages($_GET['domain']);
} catch (Throwable $e) {
    // Error may happen when overrided translation files does not use same plural rules as GLPI.
    ErrorHandler::logCaughtException($e);
}
if (!($messages instanceof TextDomain)) {
    // No TextDomain found means that there is no translations for given domain.
    // It is mostly related to plugins that does not provide any translations.
    echo $default_response;
    return;
}

// Extract headers from main po file
$po_file = GLPI_ROOT . '/locales/' . preg_replace(
    '/\.mo$/',
    '.po',
    $CFG_GLPI['languages'][$_SESSION['glpilanguage']][1]
);
$po_file_handle = fopen(
    $po_file,
    'rb'
);
if (false === $po_file_handle) {
    trigger_error(sprintf('Unable to extract locales data from "%s".', $po_file), E_USER_WARNING);
    echo $default_response;
    return;
}
$in_headers = false;
$headers = [];
$header_keys = ['language', 'plural-forms'];
while (false !== ($line = fgets($po_file_handle))) {
    if (preg_match('/^msgid\s+""\s*$/', $line)) {
        $in_headers = true;
        continue;
    }
    if ($in_headers && preg_match('/^msgid\s+".*"\s*$/', $line)) {
        break; // new msgid = end of headers parsing
    }
    $header = [];
    if ($in_headers && preg_match('/^"(?P<name>[a-z-]+):\s*(?P<value>.*)\\\n"\s*$/i', $line, $header)) {
        $header_name = strtolower($header['name']);
        $header_value = $header['value'];
        if (in_array($header_name, $header_keys)) {
            $headers[$header_name] = $header_value;
        }
    }
}
if (count(array_diff($header_keys, array_keys($headers))) > 0) {
    trigger_error(sprintf('Missing mandatory locale headers in "%s".', $po_file), E_USER_WARNING);
    echo $default_response;
    return;
}

// Output messages and headers
$messages[''] = $headers;
$messages->ksort();
echo(json_encode($messages));
