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
 */
function __($str, $domain = 'glpi')
{
    /** @var \Laminas\I18n\Translator\TranslatorInterface $TRANSLATE */
    global $TRANSLATE;

    $trans = null;

    if ($TRANSLATE !== null) {
        try {
            $trans = $TRANSLATE->translate($str, $domain);

            if (is_array($trans)) {
                // Wrong call when plural defined
                $trans = $trans[0];
            }
        } catch (\Throwable $e) {
            // Error may happen when overrided translation files does not use same plural rules as GLPI.
            // Silently fail to not flood error log.
        }
    }

    return $trans ?? $str;
}


/**
 * Translate a string and escape HTML entities
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
    return htmlentities(__($str, $domain), ENT_QUOTES, 'UTF-8');
}


/**
 * Translate a contextualized string and escape HTML entities
 *
 * @since 0.84
 *
 * @param string $ctx    context
 * @param string $str    to translate
 * @param string $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sx($ctx, $str, $domain = 'glpi')
{
    return htmlentities(_x($ctx, $str, $domain), ENT_QUOTES, 'UTF-8');
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
 */
function _n($sing, $plural, $nb, $domain = 'glpi')
{
    /** @var \Laminas\I18n\Translator\TranslatorInterface $TRANSLATE */
    global $TRANSLATE;

    $trans = null;

    if ($TRANSLATE !== null) {
        try {
            $trans = $TRANSLATE->translatePlural($sing, $plural, $nb, $domain);
        } catch (\Throwable $e) {
            // Error may happen when overrided translation files does not use same plural rules as GLPI.
            // Silently fail to not flood error log.
        }
    }

    return $trans ?? (($nb == 0 || $nb > 1) ? $plural : $sing);
}


/**
 * Pluralized translation with HTML entities escaped
 *
 * @since 0.84
 *
 * @param string  $sing   in singular
 * @param string  $plural in plural
 * @param integer $nb     to select singular or plural
 * @param string  $domain domain used (default is glpi, may be plugin name)
 *
 * @return string protected string (with htmlentities)
 */
function _sn($sing, $plural, $nb, $domain = 'glpi')
{
    return htmlentities(_n($sing, $plural, $nb, $domain), ENT_QUOTES, 'UTF-8');
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
