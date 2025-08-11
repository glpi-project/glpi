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

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * @var int $DEFAULT_PLURAL_NUMBER
 */
global $DEFAULT_PLURAL_NUMBER;
$DEFAULT_PLURAL_NUMBER = 2;

/**
 * Translate a string
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 *
 * @psalm-taint-source html (translated message can contain unexpected HTML special chars)
 * @psalm-taint-source has_quotes (translated message can contain quotes not present in the translation key)
 */
function __($str, $domain = 'glpi')
{
    global $TRANSLATE;

    $trans = null;

    try {
        $trans = $TRANSLATE->translate($str, $domain);

        if (is_array($trans)) {
            // Wrong call when plural defined
            $trans = $trans[0];
        }
    } catch (Throwable $e) {
        // Error may happen when overrided translation files does not use same plural rules as GLPI.
        // Silently fail to not flood error log.
    }

    return $trans ?? $str;
}


/**
 * Translate a string and escape HTML special chars.
 *
 * @since 0.84
 *
 * @param string $str    String to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function __s($str, $domain = 'glpi')
{
    return htmlspecialchars(__($str, $domain));
}


/**
 * Translate a contextualized string and escape HTML special chars.
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _sx($ctx, $str, $domain = 'glpi')
{
    return htmlspecialchars(_x($ctx, $str, $domain));
}


/**
 * Pluralized translation
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string translated string
 *
 * @psalm-taint-source html (translated message can contain unexpected HTML special chars)
 * @psalm-taint-source has_quotes (translated message can contain quotes not present in the translation key)
 */
function _n($sing, $plural, $nb, $domain = 'glpi')
{
    /** @var TranslatorInterface $TRANSLATE */
    global $TRANSLATE;

    $trans = null;

    try {
        $trans = $TRANSLATE->translatePlural($sing, $plural, $nb, $domain);
    } catch (Throwable $e) {
        // Error may happen when overrided translation files does not use same plural rules as GLPI.
        // Silently fail to not flood error log.
    }

    return $trans ?? (($nb == 0 || $nb > 1) ? $plural : $sing);
}


/**
 * Pluralized translation with HTML special chars escaped
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _sn($sing, $plural, $nb, $domain = 'glpi')
{
    return htmlspecialchars(_n($sing, $plural, $nb, $domain));
}


/**
 * Contextualized translation
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _x($ctx, $str, $domain = 'glpi')
{

    // simulate pgettext
    $msg   = $ctx . "\004" . $str;
    $trans = __($msg, $domain);

    if ($trans == $msg) {
        // No translation
        return $str;
    }
    return $trans;
}


/**
 * Pluralized contextualized translation
 *
 * @since 0.84
 *
 * @param string  $ctx    context
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string
 */
function _nx($ctx, $sing, $plural, $nb, $domain = 'glpi')
{

    // simulate pgettext
    $singmsg    = $ctx . "\004" . $sing;
    $pluralmsg  = $ctx . "\004" . $plural;
    $trans      = _n($singmsg, $pluralmsg, $nb, $domain);

    if ($trans == $singmsg) {
        // No translation
        return $sing;
    }
    if ($trans == $pluralmsg) {
        // No translation
        return $plural;
    }
    return $trans;
}
