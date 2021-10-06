<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

namespace Glpi\Toolbox;

use Toolbox;

class Sanitizer {

   private const CHARS_MAPPING = [
      '&'  => '&#38;',
      '<'  => '&#60;',
      '>'  => '&#62;',
   ];

   private const LEGACY_CHARS_MAPPING = [
      '<'  => '&lt;',
      '>'  => '&gt;',
   ];

   /**
    * Sanitize a value. Resulting value will have its HTML special chars converted into entities
    * and would be printable in a HTML document without having to be escaped.
    *
    * @param mixed $value
    * @param bool  $db_escape
    *
    * @return mixed
    */
   public static function sanitize($value, bool $db_escape = false) {
      if (is_array($value)) {
         return array_map(
            function ($val) use ($db_escape) {
               return self::sanitize($val, $db_escape);
            },
            $value
         );
      }

      if (!is_string($value)) {
         return $value;
      }

      if (preg_match('/^[a-zA-Z0-9_\\\]+$/', $value) && class_exists($value)) {
         // Do not sanitize values that corresponds to an existing class, as classnames are considered safe
         return $value;
      }

      if ($db_escape) {
         $value = self::dbEscape($value);
      }

      $mapping = self::CHARS_MAPPING;
      return str_replace(array_keys($mapping), array_values($mapping), $value);
   }

   /**
    * Unsanitize a value. Reverts self::sanitize() transformation.
    *
    * @param mixed $value
    * @param bool  $db_unescape
    *
    * @return mixed
    */
   public static function unsanitize($value, bool $db_unescape = false) {
      if (is_array($value)) {
         return array_map(
            function ($val) use ($db_unescape) {
               return self::unsanitize($val, $db_unescape);
            },
            $value
         );
      }
      if (!is_string($value)) {
         return $value;
      }

      $mapping = null;
      foreach (self::CHARS_MAPPING as $htmlentity) {
         if (strpos($value, $htmlentity) !== false) {
            // Value was cleaned using new char mapping, so it must be uncleaned with same mapping
            $mapping = self::CHARS_MAPPING;
            break;
         }
      }
      if ($mapping === null) {
         $mapping = self::LEGACY_CHARS_MAPPING; // Fallback to legacy chars mapping
      }

      $mapping = array_reverse($mapping);
      $value = str_replace(array_values($mapping), array_keys($mapping), $value);

      if ($db_unescape) {
         $value = self::dbUnescape($value);
      }

      return $value;
   }

   /**
    * Check if value is sanitized.
    *
    * @param string $value
    *
    * @return bool
    */
   public static function isSanitized(string $value): bool {
      $special_chars_pattern   = '/(<|>|(&(?!#?[a-z0-9]+;)))/i';
      $sanitized_chars = array_merge(
         array_values(self::CHARS_MAPPING),
         array_values(self::LEGACY_CHARS_MAPPING)
      );
      $sanitized_chars_pattern = '/(' . implode('|', $sanitized_chars) . ')/';

      return preg_match($special_chars_pattern, $value) === 0
         && preg_match($sanitized_chars_pattern, $value) === 1;
   }

   /**
    * Return verbatim value for an itemtype field.
    * Returned value will be unsanitized if it has been transformed by GLPI sanitizing process.
    *
    * @param string $value
    *
    * @return string
    */
   public static function getVerbatimValue(string $value): string {
      return Sanitizer::isSanitized($value) ? Sanitizer::unsanitize($value) : $value;
   }

   /**
    * Escape special chars to protect DB queries.
    *
    * @param string $value
    *
    * @return string
    */
   private static function dbEscape(string $value): string {
      // TODO Toolbox::addslashes_deep() should be moved in current class,
      // but it is widely used, so it will be done later.
      return Toolbox::addslashes_deep($value);
   }

   /**
    * Revert `mysqli::real_escape_string()` transformation.
    * Inspired by https://stackoverflow.com/a/38769977
    *
    * @param string $value
    *
    * @return string
    */
   private static function dbUnescape(string $value): string {
      // stripslashes cannot be used here as it would produce "r" and "n" instead of "\r" and \n".

      $search  = ['x00', 'n', 'r', '\\', '\'', '"','x1a'];
      $replace = ["\x00", "\n", "\r", "\\", "'", "\"", "\x1a"];
      for ($i = 0; $i < strlen($value); $i++) {
         if (substr($value, $i, 1) == '\\') {
            foreach ($search as $index => $char) {
               if ($i <= strlen($value) - strlen($char) && substr($value, $i + 1, strlen($char)) == $char) {
                  $value = substr_replace($value, $replace[$index], $i, strlen($char) + 1);
                  break;
               }
            }
         }
      }
      return $value;
   }
}
