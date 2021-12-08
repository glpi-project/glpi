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
    * Also, DB special chars can be escaped to prevent SQL injections.
    *
    * @param mixed $value
    * @param bool  $db_escape
    *
    * @return mixed
    */
   public static function sanitize($value, bool $db_escape = true) {
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

      return self::encodeHtmlSpecialChars($value);
   }

   /**
    * Unsanitize a value. Reverts self::sanitize() transformation.
    *
    * @param mixed $value
    *
    * @return mixed
    */
   public static function unsanitize($value) {
      if (is_array($value)) {
         return array_map(
            function ($val) {
               return self::unsanitize($val);
            },
            $value
         );
      }
      if (!is_string($value)) {
         return $value;
      }

      if (self::isHtmlEncoded($value)) {
         $value = self::decodeHtmlSpecialChars($value);
      }

      if (self::isDbEscaped($value)) {
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
   public static function isHtmlEncoded(string $value): bool {
      // A value is Html Encoded if it does not contains
      // - `<`;
      // - `>`;
      // - `&` not followed by an HTML entity identifier;
      // and if it contains any entity used to encode HTML special chars during sanitization process.
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
    * Check if value is escaped for DB usage.
    * A value is considered as escaped if it special char (NULL, \n, \r, \, ', " and EOF) that has been escaped.
    *
    * @param string $value
    *
    * @return string
    */
   public static function isDbEscaped(string $value): bool {
      // Search for unprotected control chars `NULL`, `\n`, `\r` and `EOF`.
      $control_chars = ["\x00", "\n", "\r", "\x1a"];
      for ($i = 0; $i < strlen($value); $i++) {
         foreach ($control_chars as $char) {
            if ($i + strlen($char) <= strlen($value) && substr($value, $i, strlen($char)) == $char) {
               return false; // Unprotected control char found
            }
         }
      }

      // Search for unprotected quotes.
      $quotes = ["'", '"'];
      for ($i = 0; $i < strlen($value); $i++) {
         foreach ($quotes as $char) {
            if (substr($value, $i, 1) != $char) {
               continue;
            }
            if ($i === 0 || substr($value, $i - 1, 1) !== '\\') {
               return false; // Unprotected quote found
            }
         }
      }

      $has_special_chars = false;

      // Search for unprotected backslashes.
      $special_chars = ['\x00', '\n', '\r', "\'", '\"', '\x1a'];
      $backslashes_count = 0;
      for ($i = 0; $i < strlen($value); $i++) {
         if (substr($value, $i, 1) != '\\') {
            continue;
         }
         $has_special_chars = true;

         // Count successive backslashes.
         $backslashes_count = 1;
         while ($i + 1 <= strlen($value) && substr($value, $i + 1, 1) == '\\') {
            $backslashes_count++;
            $i++;
         }

         // Check if last backslash is related to an escaped special char.
         foreach ($special_chars as $char) {
            if ($i + strlen($char) <= strlen($value) && substr($value, $i, strlen($char)) == $char) {
               $backslashes_count--;
               break;
            }
         }

         // Backslashes are escaped only if there is odd count of them.
         if ($backslashes_count % 2 === 1) {
            return false; // Unprotected backslash or quote found
         }
      }

      return $has_special_chars;
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
      return Sanitizer::unsanitize($value);
   }

   /**
    * Encode HTML special chars, to prevent XSS when value is printed without using any filter.
    *
    * @param string $value
    *
    * @return string
    */
   private static function encodeHtmlSpecialChars(string $value): string {
      $mapping = self::CHARS_MAPPING;
      return str_replace(array_keys($mapping), array_values($mapping), $value);
   }

   /**
    * Decode HTML special chars.
    *
    * @param string $value
    *
    * @return string
    */
   private static function decodeHtmlSpecialChars(string $value): string {
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
      return str_replace(array_values($mapping), array_keys($mapping), $value);
   }

   /**
    * Escape special chars to protect DB queries.
    *
    * @param string $value
    *
    * @return string
    */
   private static function dbEscape(string $value): string {
      global $DB;
      return $DB->escape($value);
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

      $search  = ['x00', 'n', 'r', '\\', '\'', '"', 'x1a'];
      $replace = ["\x00", "\n", "\r", "\\", "'", "\"", "\x1a"];
      for ($i = 0; $i < strlen($value); $i++) {
         if (substr($value, $i, 1) != '\\') {
            continue;
         }
         foreach ($search as $index => $char) {
            if ($i + strlen($char) <= strlen($value) && substr($value, $i + 1, strlen($char)) == $char) {
               $value = substr_replace($value, $replace[$index], $i, strlen($char) + 1);
               break;
            }
         }
      }
      return $value;
   }
}
