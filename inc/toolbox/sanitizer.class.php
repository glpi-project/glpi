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
      '>'  =>  '&gt;',
   ];

   public static function sanitize($value, bool $add_slashes = false) {
      if (is_array($value)) {
         return array_map([__CLASS__, __METHOD__], $value);
      }
      if (!is_string($value)) {
         return $value;
      }

      if ($add_slashes) {
         // TODO Toolbox::addslashes_deep() should be moved in current class,
         // but it is widely used, so it will be done later.
         $value = Toolbox::addslashes_deep($value);
      }

      $mapping = self::CHARS_MAPPING;
      return str_replace(array_keys($mapping), array_values($mapping), $value);
   }

   public static function unsanitize($value, bool $strip_slashes = false) {
      if (is_array($value)) {
         return array_map([__CLASS__, __METHOD__], $value);
      }
      if (!is_string($value)) {
         return $value;
      }

      if ($strip_slashes) {
         $value = stripslashes($value);
      }

      $mapping = null;
      foreach (self::CHARS_MAPPING as $htmlentity) {
         if (strpos($value, $htmlentity) !== false) {
            // Value was cleaned using new char mapping, so it must be uncleaned with same mapping
            $mapping = self::CHARS_MAPPING;
            break;
         }
      }

      // Fallback to legacy chars mapping
      if ($mapping === null) {
         $mapping = self::LEGACY_CHARS_MAPPING;
      }

      $mapping = array_reverse($mapping);
      return str_replace(array_values($mapping), array_keys($mapping), $value);
   }

   public static function isSanitized(string $value): bool {
      $special_chars_pattern   = '/(<|>|(&(?!#?[a-z0-9]+;)))/i';
      $sanitized_chars_pattern = '/(' . implode('|', array_merge(self::CHARS_MAPPING, self::LEGACY_CHARS_MAPPING)) . ')/';

      return preg_match($special_chars_pattern, $value) === 0
         && preg_match($sanitized_chars_pattern, $value) === 1;
   }
}
