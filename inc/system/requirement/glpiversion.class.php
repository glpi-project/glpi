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

namespace Glpi\System\Requirement;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 9.5.0
 */
class GlpiVersion extends AbstractRequirement {

   /**
    * Minimal required GLPI version.
    *
    * @var string
    */
   private $min_version;

   /**
    * Maximal required GLPI version (exclusive).
    *
    * @var string|null
    */
   private $max_version;

   /**
    * @param string $min_version  Minimal required GLPI version
    * @param string $max_version  Maximal required GLPI version (exclusive)
    */
   public function __construct(?string $min_version = null, ?string $max_version = null) {
      $this->title = __('Testing GLPI version');
      $this->min_version = $min_version;
      $this->max_version = $max_version;
   }

   protected function check() {
      if ($this->min_version === null && $this->max_version === null) {
         throw new \LogicException('Either min or max versions must be defined');
      }

      $glpiVersion = defined('GLPI_PREVER') ? GLPI_PREVER : GLPI_VERSION;
      $is_min_ok = $this->min_version !== null ? version_compare($glpiVersion, $this->min_version, '>=') : true;
      $is_max_ok = $this->max_version !== null ? version_compare($glpiVersion, $this->max_version, '<') : true;

      $this->validated = $is_min_ok && $is_max_ok;

      if ($this->min_version !== null && $this->max_version === null) {
         $this->validation_messages[] = $this->validated
            ? sprintf(__('GLPI version is >= %s.'), $this->min_version)
            : sprintf(__('GLPI version must be >= %s.'), $this->min_version);
      } else if ($this->min_version === null && $this->max_version !== null) {
         $this->validation_messages[] = $this->validated
            ? sprintf(__('GLPI version is < %s.'), $this->max_version)
            : sprintf(__('GLPI version must be < %s.'), $this->max_version);
      } else {
         $this->validation_messages[] = $this->validated
            ? sprintf(__('GLPI version is >= %s and < %s.'), $this->min_version, $this->max_version)
            : sprintf(__('GLPI version must be >= %s and < %s.'), $this->min_version, $this->max_version);
      }
   }
}
